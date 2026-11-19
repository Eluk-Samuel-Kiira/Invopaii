<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PaypalProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://api-m.sandbox.paypal.com',
        'live' => 'https://api-m.paypal.com',
    ];

    /**
     * OAuth access token. Cached because PayPal tokens last ~9 hours.
     */
    protected function accessToken(string $mode, ProviderCredential $credential): string
    {
        $cacheKey = 'paypal_token_' . $credential->id . '_' . $mode;

        return Cache::remember($cacheKey, now()->addHours(8), function () use ($mode, $credential) {
            $response = $this->http($mode, [
                'Authorization' => 'Basic ' . base64_encode(
                    ($credential->public_key ?? '') . ':' . ($credential->secret_key ?? '')
                ),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

            $data = $response->json();
            $token = $data['access_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('PayPal OAuth failed: ' . $response->body());
            }

            return $token;
        });
    }

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;

        // PayPal Orders API v2 flow:
        // 1. POST /v2/checkout/orders → creates an order, returns an "approve" link
        // 2. Redirect customer to the approve link
        // 3. Customer approves on PayPal
        // 4. PayPal redirects back to return_url with a token in the query
        // 5. POST /v2/checkout/orders/{id}/capture → captures the funds

        try {
            $token = $this->accessToken($attempt->mode, $credential);
        } catch (\Throwable $e) {
            return ProviderResult::failure('PayPal auth failed: ' . $e->getMessage(), 'auth_failed');
        }

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $payment->public_id,
                'description' => $payment->description ?? 'Payment',
                'custom_id' => $payment->reference,
                'amount' => [
                    'currency_code' => strtoupper($payment->currency),
                    'value' => number_format($this->toMajor($payment->amount, $payment->currency), 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => $payment->company?->name,
                'return_url' => $payment->return_url ?? rtrim(config('app.url'), '/') . '/paypal/return',
                'cancel_url' => $payment->metadata['cancel_url'] ?? rtrim(config('app.url'), '/') . '/paypal/cancel',
                'user_action' => 'PAY_NOW',
            ],
        ];

        try {
            // TODO: POST /v2/checkout/orders with Bearer token
            // $response = $this->http($attempt->mode, [
            //     'Authorization' => 'Bearer ' . $token,
            // ])->post('/v2/checkout/orders', $payload);
            //
            // Response contains: { id, status: "CREATED", links: [{ rel: "approve", href: "..." }, ...] }
            // Extract the "approve" link and return as next_action.

            // When wired, this should return:
            //   ProviderResult::pending(
            //       providerReference: $data['id'],
            //       providerStatus: 'CREATED',
            //       nextActionType: 'redirect',
            //       nextAction: ['url' => $approveUrl],
            //   );

            return ProviderResult::failure(
                'PayPal integration not yet enabled.',
                'integration_pending'
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('PayPal charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function capture(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        // Called after the customer approves on PayPal.
        // POST /v2/checkout/orders/{id}/capture

        $orderId = $payment->provider_reference;
        if (!$orderId) {
            return ProviderResult::failure('No PayPal order to capture.', 'no_reference');
        }

        // TODO: implement POST /v2/checkout/orders/{$orderId}/capture
        // Response: { id, status: "COMPLETED", purchase_units: [{ payments: { captures: [{ id, status }] } }] }

        return ProviderResult::failure('PayPal capture not yet enabled.', 'integration_pending');
    }

    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult
    {
        // The capture_id (not the order_id) is required for refunds.
        // POST /v2/payments/captures/{capture_id}/refund

        // TODO: pull capture_id from the payment's response_payload, then POST the refund

        return ProviderResult::failure('PayPal refunds not yet enabled.', 'integration_pending');
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // PayPal verifies webhooks via an API round-trip.
        // POST /v1/notifications/verify-webhook-signature with the raw body and headers.
        // Response: { verification_status: "SUCCESS" | "FAILURE" }

        $payload = $request->all();

        $eventType = match (strtoupper($payload['event_type'] ?? '')) {
            'CHECKOUT.ORDER.APPROVED' => 'payment.processing',
            'PAYMENT.CAPTURE.COMPLETED' => 'payment.succeeded',
            'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.REVERSED' => 'payment.failed',
            'PAYMENT.CAPTURE.REFUNDED' => 'payment.refunded',
            default => 'payment.updated',
        };

        return [
            'valid' => true, // TODO: implement verification API call
            'event_type' => $eventType,
            'provider_event_id' => $payload['id'] ?? null,
            'provider_reference' => $payload['resource']['id'] ?? null,
            'payload' => $payload,
        ];
    }

    protected function toMajor(int $minor, string $currency): float
    {
        return in_array($currency, ['JPY', 'KRW'], true)
            ? (float) $minor
            : round($minor / 100, 2);
    }
}
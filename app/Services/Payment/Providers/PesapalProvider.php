<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PesapalProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://cybqa.pesapal.com/pesapalv3',
        'live' => 'https://pay.pesapal.com/v3',
    ];

    /**
     * Pesapal v3 uses a Bearer token obtained via POST /api/Auth/RequestToken.
     */
    protected function accessToken(string $mode, ProviderCredential $credential): string
    {
        $cacheKey = 'pesapal_token_' . $credential->id . '_' . $mode;

        return Cache::remember($cacheKey, now()->addMinutes(4), function () use ($mode, $credential) {
            // Pesapal tokens only last 5 minutes — cache for 4 to be safe.
            $response = $this->http($mode)->post('/api/Auth/RequestToken', [
                'consumer_key' => $credential->public_key,
                'consumer_secret' => $credential->secret_key,
            ]);

            $data = $response->json();
            $token = $data['token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('Pesapal auth failed: ' . $response->body());
            }

            return $token;
        });
    }

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;
        $mode = $attempt->mode;

        try {
            $token = $this->accessToken($mode, $credential);
        } catch (\Throwable $e) {
            return ProviderResult::failure('Pesapal auth failed: ' . $e->getMessage(), 'auth_failed');
        }

        // Pesapal v3 SubmitOrderRequest flow:
        // 1. POST /api/Transactions/SubmitOrderRequest
        //    → returns { order_tracking_id, merchant_reference, redirect_url }
        // 2. Redirect customer to redirect_url
        // 3. Customer pays on Pesapal's hosted page
        // 4. Pesapal calls your IPN URL (configured separately)
        // 5. GET /api/Transactions/GetTransactionStatus?orderTrackingId={id}

        $payload = [
            'id' => $payment->reference ?? $payment->public_id,
            'currency' => strtoupper($payment->currency),
            'amount' => $this->toMajor($payment->amount, $payment->currency),
            'description' => $payment->description ?? 'Payment',
            'callback_url' => rtrim(config('app.url'), '/') . '/webhooks/pesapal',
            'notification_id' => $credential->extra['ipn_id'] ?? null, // Pesapal's IPN registration ID
            'billing_address' => [
                'email_address' => $payment->receipt_email ?? $payment->customer?->email,
                'phone_number' => $payment->paymentMethod?->msisdn,
                'country_code' => $payment->customer_country,
                'first_name' => $payment->customer?->first_name,
                'last_name' => $payment->customer?->last_name,
            ],
        ];

        try {
            // TODO: POST /api/Transactions/SubmitOrderRequest with Bearer token
            // $response = $this->http($mode, [
            //     'Authorization' => 'Bearer ' . $token,
            // ])->post('/api/Transactions/SubmitOrderRequest', $payload);
            //
            // Response: {
            //   order_tracking_id: "...",
            //   merchant_reference: "...",
            //   redirect_url: "https://pay.pesapal.com/iframe/PesapalIframe3/Index?OrderTrackingId=...",
            //   status: "200"
            // }
            //
            // Return ProviderResult::pending with next_action = redirect to redirect_url.

            return ProviderResult::failure(
                'Pesapal integration not yet enabled. Awaiting approval.',
                'integration_pending'
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('Pesapal charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $orderTrackingId = $payment->provider_reference;
        if (!$orderTrackingId) {
            return ProviderResult::failure('No Pesapal order to verify.', 'no_reference');
        }

        try {
            $token = $this->accessToken($payment->mode, $credential);

            // TODO: GET /api/Transactions/GetTransactionStatus?orderTrackingId={id}
            // $response = $this->http($payment->mode, [
            //     'Authorization' => 'Bearer ' . $token,
            // ])->get('/api/Transactions/GetTransactionStatus', [
            //     'orderTrackingId' => $orderTrackingId,
            // ]);
            //
            // Response includes:
            //   payment_status_description: "Completed" | "Failed" | "Pending" | "Reversed"
            //   status_code: 1 = completed, 0 = failed, 2 = reversed
            //
            // Map status_code:
            //   1 → ProviderResult::success
            //   0 → ProviderResult::failure
            //   2 → ProviderResult::failure with 'reversed'

            return ProviderResult::failure('Pesapal verify not yet enabled.', 'integration_pending');
        } catch (\Throwable $e) {
            return ProviderResult::failure('Pesapal verify exception: ' . $e->getMessage(), 'verify_exception');
        }
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // Pesapal IPN sends a GET request to your callback with query params:
        //   ?OrderTrackingId=xxx&OrderMerchantReference=xxx&OrderNotificationType=IPNCHANGE
        //
        // The real verification is a follow-up GET to GetTransactionStatus.

        $payload = $request->all();
        $orderTrackingId = $payload['OrderTrackingId'] ?? $payload['order_tracking_id'] ?? null;

        // Status isn't in the IPN — you must poll to find out
        return [
            'valid' => true,
            'event_type' => 'payment.updated', // Generic — real status comes from verifyPayment
            'provider_event_id' => $orderTrackingId . ':' . ($payload['OrderNotificationType'] ?? 'ipn'),
            'provider_reference' => $orderTrackingId,
            'payload' => $payload,
        ];
    }

    protected function toMajor(int $minor, string $currency): float
    {
        return in_array($currency, ['UGX', 'RWF', 'TZS'], true)
            ? (float) $minor
            : round($minor / 100, 2);
    }
}
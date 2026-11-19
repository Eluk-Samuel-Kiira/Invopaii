<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;

class FlutterwaveProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://api.flutterwave.com/v3',
        'live' => 'https://api.flutterwave.com/v3',
    ];

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;

        // Flutterwave supports card, mobile_money, bank_transfer, ussd
        $methodType = $payment->payment_method_type ?? 'card';

        // TODO: build payload per method
        // Card  → POST /charges?type=card with encrypted card details, or use a token
        // Mobile Money → POST /charges?type=mobile_money_uganda (or _kenya, _ghana, etc.)
        //   with { phone_number, network, amount, currency, email, tx_ref }
        // Bank Transfer → POST /charges?type=bank_transfer
        // USSD → POST /charges?type=ussd

        $payload = [
            'tx_ref' => $payment->reference ?? $payment->public_id,
            'amount' => $this->toMajor($payment->amount, $payment->currency),
            'currency' => $payment->currency,
            'redirect_url' => $payment->return_url,
            'customer' => [
                'email' => $payment->receipt_email ?? $payment->customer?->email,
                'phonenumber' => $payment->paymentMethod?->msisdn,
                'name' => $payment->customer?->name,
            ],
            'customizations' => [
                'title' => $payment->description ?? 'Payment',
                'description' => $payment->description,
            ],
            'meta' => [
                'payment_id' => $payment->public_id,
                'company_id' => $payment->company_id,
            ],
        ];

        try {
            // TODO: send the request
            // $response = $this->http($attempt->mode, [
            //     'Authorization' => 'Bearer ' . $credential->secret_key,
            // ])->post('/charges?type=' . $methodType, $payload);

            // Placeholder — remove when real call is wired
            return ProviderResult::failure(
                'Flutterwave integration not yet enabled. Awaiting approval.',
                'integration_pending'
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('Flutterwave charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $reference = $payment->provider_reference;
        if (!$reference) {
            return ProviderResult::failure('No Flutterwave reference to verify.', 'no_reference');
        }

        // TODO: GET /transactions/{id}/verify
        // $response = $this->http($payment->mode, [
        //     'Authorization' => 'Bearer ' . $credential->secret_key,
        // ])->get("/transactions/{$reference}/verify");

        // TODO: map $response->json()['data']['status']:
        //   'successful' → ProviderResult::success(...)
        //   'pending'    → ProviderResult::pending(...)
        //   'failed'     → ProviderResult::failure(...)

        return ProviderResult::failure('Flutterwave verify not yet enabled.', 'integration_pending');
    }

    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult
    {
        // TODO: POST /transactions/{id}/refund
        // $response = $this->http($payment->mode, [
        //     'Authorization' => 'Bearer ' . $credential->secret_key,
        // ])->post("/transactions/{$payment->provider_reference}/refund", [
        //     'amount' => $this->toMajor($amount, $payment->currency),
        //     'comments' => $reason,
        // ]);

        return ProviderResult::failure('Flutterwave refunds not yet enabled.', 'integration_pending');
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // Flutterwave sends a "verif-hash" header matching the webhook secret hash
        // you configure in their dashboard.
        $signature = $request->header('verif-hash');
        $expected = $credential->webhook_secret;

        $valid = $signature && $expected && hash_equals($expected, $signature);

        $payload = $request->all();
        $status = strtolower($payload['data']['status'] ?? $payload['status'] ?? '');

        $eventType = match ($status) {
            'successful', 'success' => 'payment.succeeded',
            'failed' => 'payment.failed',
            'pending' => 'payment.processing',
            default => 'payment.updated',
        };

        return [
            'valid' => $valid,
            'event_type' => $eventType,
            'provider_event_id' => $payload['data']['id'] ?? $payload['id'] ?? null,
            'provider_reference' => $payload['data']['id'] ?? null,
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
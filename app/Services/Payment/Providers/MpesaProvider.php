<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MpesaProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://sandbox.safaricom.co.ke',
        'live' => 'https://api.safaricom.co.ke',
    ];

    protected function accessToken(string $mode, ProviderCredential $credential): string
    {
        $cacheKey = 'mpesa_token_' . $credential->id . '_' . $mode;

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($mode, $credential) {
            $response = $this->http($mode, [
                'Authorization' => 'Basic ' . base64_encode(
                    ($credential->public_key ?? '') . ':' . ($credential->secret_key ?? '')
                ),
            ])->get('/oauth/v1/generate?grant_type=client_credentials');

            $data = $response->json();
            $token = $data['access_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('M-PESA OAuth failed: ' . $response->body());
            }

            return $token;
        });
    }

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;
        $mode = $attempt->mode;

        if ($payment->payment_method_type !== 'mobile_money') {
            return ProviderResult::failure('M-PESA only supports mobile_money.', 'unsupported_method');
        }

        $msisdn = $credential->extra['test_msisdn']
            ?? $payment->paymentMethod?->msisdn
            ?? $payment->metadata['msisdn']
            ?? null;

        if (!$msisdn) {
            return ProviderResult::failure('No MSISDN provided.', 'missing_msisdn');
        }

        // Normalize to 254XXXXXXXXX
        $clean = preg_replace('/\D/', '', $msisdn);
        if (str_starts_with($clean, '0')) {
            $clean = '254' . substr($clean, 1);
        } elseif (!str_starts_with($clean, '254')) {
            $clean = '254' . $clean;
        }

        try {
            $token = $this->accessToken($mode, $credential);
        } catch (\Throwable $e) {
            return ProviderResult::failure('M-PESA auth failed: ' . $e->getMessage(), 'auth_failed');
        }

        // Build the password: base64(Shortcode + Passkey + Timestamp)
        $shortcode = $credential->extra['shortcode'] ?? $credential->merchant_account_id;
        $passkey = $credential->extra['passkey'] ?? '';
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        // M-PESA amount is always whole KES (integer)
        $amount = (int) ceil($payment->amount / 100);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $credential->extra['transaction_type'] ?? 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $clean,
            'PartyB' => $shortcode,
            'PhoneNumber' => $clean,
            'CallBackURL' => $payment->return_url
                ?? rtrim(config('app.url'), '/') . '/webhooks/mpesa',
            'AccountReference' => substr($payment->reference ?? $payment->public_id, 0, 12),
            'TransactionDesc' => substr($payment->description ?? 'Payment', 0, 13),
        ];

        try {
            $response = $this->http($mode, [
                'Authorization' => 'Bearer ' . $token,
            ])->post('/mpesa/stkpush/v1/processrequest', $payload);

            $data = $response->json() ?? [];
            $responseCode = $data['ResponseCode'] ?? null;

            if ($responseCode === '0') {
                return ProviderResult::pending(
                    providerReference: $data['CheckoutRequestID'] ?? null,
                    providerStatus: 'PENDING',
                    providerMessage: $data['CustomerMessage'] ?? 'STK push sent',
                    responsePayload: $data,
                    nextActionType: 'wait_for_push',
                    nextAction: [
                        'message' => 'Enter your M-PESA PIN on your phone.',
                        'merchant_request_id' => $data['MerchantRequestID'] ?? null,
                        'checkout_request_id' => $data['CheckoutRequestID'] ?? null,
                    ],
                );
            }

            return ProviderResult::failure(
                message: $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'M-PESA STK push failed',
                code: $data['errorCode'] ?? 'mpesa_' . ($responseCode ?? $response->status()),
                responsePayload: $data,
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('M-PESA charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $checkoutRequestId = $payment->provider_reference;
        if (!$checkoutRequestId) {
            return ProviderResult::failure('No M-PESA reference to verify.', 'no_reference');
        }

        try {
            $token = $this->accessToken($payment->mode, $credential);
            $shortcode = $credential->extra['shortcode'] ?? $credential->merchant_account_id;
            $passkey = $credential->extra['passkey'] ?? '';
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($shortcode . $passkey . $timestamp);

            $response = $this->http($payment->mode, [
                'Authorization' => 'Bearer ' . $token,
            ])->post('/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

            $data = $response->json() ?? [];
            $resultCode = $data['ResultCode'] ?? null;

            // M-PESA ResultCode 0 = success, 1032 = cancelled by user, 1037 = timeout
            if ($resultCode === '0' || $resultCode === 0) {
                return ProviderResult::success(
                    providerReference: $checkoutRequestId,
                    providerStatus: 'SUCCESS',
                    responsePayload: $data,
                    authorizationCode: $data['ResultDesc'] ?? null,
                );
            }

            if ($resultCode === null) {
                return ProviderResult::pending(
                    providerReference: $checkoutRequestId,
                    providerStatus: 'PENDING',
                    responsePayload: $data,
                );
            }

            return ProviderResult::failure(
                message: $data['ResultDesc'] ?? 'M-PESA payment failed',
                code: 'mpesa_' . $resultCode,
                providerReference: $checkoutRequestId,
                responsePayload: $data,
            );
        } catch (\Throwable $e) {
            return ProviderResult::failure('M-PESA verify exception: ' . $e->getMessage(), 'verify_exception');
        }
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // M-PESA callbacks arrive as JSON with a Body.stkCallback structure.
        // There is no signature — you trust the source IP or verify by polling.
        $payload = $request->all();

        $stk = $payload['Body']['stkCallback'] ?? null;

        if (!$stk) {
            return [
                'valid' => false,
                'event_type' => null,
                'provider_event_id' => null,
                'provider_reference' => null,
                'payload' => $payload,
            ];
        }

        $checkoutRequestId = $stk['CheckoutRequestID'] ?? null;
        $resultCode = $stk['ResultCode'] ?? null;

        $eventType = match ((string) $resultCode) {
            '0' => 'payment.succeeded',
            '1032' => 'payment.cancelled',   // user cancelled
            '1037' => 'payment.failed',      // timeout
            default => 'payment.failed',
        };

        return [
            'valid' => true, // verify by polling M-PESA's API after receiving
            'event_type' => $eventType,
            'provider_event_id' => $checkoutRequestId . ':' . $resultCode,
            'provider_reference' => $checkoutRequestId,
            'payload' => $payload,
        ];
    }
}
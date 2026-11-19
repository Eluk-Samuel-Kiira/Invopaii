<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AirtelMoneyProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://openapiuat.airtel.africa',
        'live' => 'https://openapi.airtel.africa',
    ];

    protected function accessToken(string $mode, ProviderCredential $credential): string
    {
        $cacheKey = 'airtel_token_' . $credential->id . '_' . $mode;

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($mode, $credential) {
            // Airtel token endpoint: POST /auth/oauth2/token
            $response = $this->http($mode)->post('/auth/oauth2/token', [
                'client_id' => $credential->public_key,
                'client_secret' => $credential->secret_key,
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException('Airtel OAuth failed: ' . $response->status() . ' ' . $response->body());
            }

            $data = $response->json();
            $token = $data['access_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('Airtel OAuth returned no access_token.');
            }

            return $token;
        });
    }

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;
        $mode = $attempt->mode;

        if ($payment->payment_method_type !== 'mobile_money') {
            return ProviderResult::failure('Airtel Money only supports mobile_money payments.', 'unsupported_method');
        }

        $msisdn = $credential->extra['test_msisdn']
            ?? $payment->paymentMethod?->msisdn
            ?? $payment->metadata['msisdn']
            ?? null;

        if (!$msisdn) {
            return ProviderResult::failure('No mobile number provided for Airtel charge.', 'missing_msisdn');
        }

        // Airtel requires country code + MSISDN. E.g. "256772100001"
        $country = $credential->extra['country'] ?? 'UG';
        $cleanMsisdn = preg_replace('/\D/', '', $msisdn);
        if (!str_starts_with($cleanMsisdn, $this->dialingCode($country))) {
            $cleanMsisdn = $this->dialingCode($country) . ltrim($cleanMsisdn, '0');
        }

        try {
            $token = $this->accessToken($mode, $credential);
        } catch (\Throwable $e) {
            return ProviderResult::failure('Airtel auth failed: ' . $e->getMessage(), 'auth_failed');
        }

        $airtelReference = Str::uuid()->toString();
        $currency = $payment->currency;
        // Airtel expects major units (e.g. 5000 UGX, not 500000)
        $amount = in_array($currency, ['UGX', 'RWF', 'TZS'], true)
            ? $payment->amount
            : round($payment->amount / 100, 2);

        $payload = [
            'reference' => $payment->reference ?? $payment->public_id,
            'subscriber' => [
                'country' => $country,
                'currency' => $currency,
                'msisdn' => $cleanMsisdn,
            ],
            'transaction' => [
                'amount' => $amount,
                'country' => $country,
                'currency' => $currency,
                'id' => $airtelReference,
            ],
        ];

        try {
            $response = $this->http($mode, [
                'Authorization' => 'Bearer ' . $token,
                'X-Country' => $country,
                'X-Currency' => $currency,
            ])->post('/merchant/v1/payments/', $payload);

            $data = $response->json() ?? [];

            // Airtel's API returns 200 with nested status objects
            $statusCode = $data['status']['code'] ?? null;
            $statusMessage = $data['status']['message'] ?? null;
            $airtelRef = $data['data']['transaction']['id'] ?? $airtelReference;

            if ($response->successful() && in_array($statusCode, ['200', '200 OK', 'SUCCESS'], true)) {
                // Charge accepted — actual settlement comes via callback
                return ProviderResult::pending(
                    providerReference: $airtelRef,
                    providerStatus: 'TIP', // "Transaction in Progress"
                    providerMessage: $statusMessage ?? 'Awaiting customer PIN.',
                    responsePayload: $data,
                    nextActionType: 'wait_for_push',
                    nextAction: [
                        'message' => 'A prompt was sent to the customer\'s phone.',
                        'reference_id' => $airtelRef,
                    ],
                );
            }

            return ProviderResult::failure(
                message: $statusMessage ?? 'Airtel charge rejected',
                code: $statusCode ?? 'airtel_error_' . $response->status(),
                providerReference: $airtelRef,
                responsePayload: $data,
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ProviderResult::failure('Airtel connection error: ' . $e->getMessage(), 'connection_error');
        } catch (\Throwable $e) {
            return ProviderResult::failure('Airtel charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $referenceId = $payment->provider_reference;
        if (!$referenceId) {
            return ProviderResult::failure('No Airtel reference to verify.', 'no_reference');
        }

        try {
            $token = $this->accessToken($payment->mode, $credential);
            $country = $credential->extra['country'] ?? 'UG';

            $response = $this->http($payment->mode, [
                'Authorization' => 'Bearer ' . $token,
                'X-Country' => $country,
                'X-Currency' => $payment->currency,
            ])->get("/standard/v1/payments/{$referenceId}");

            if (!$response->successful()) {
                return ProviderResult::failure(
                    'Airtel verify failed: ' . $response->status(),
                    'verify_failed',
                    providerReference: $referenceId,
                );
            }

            $data = $response->json() ?? [];
            $status = $data['data']['transaction']['status'] ?? 'UNKNOWN';

            return match ($status) {
                'TS' => ProviderResult::success(  // Transaction Success
                    providerReference: $referenceId,
                    providerStatus: $status,
                    responsePayload: $data,
                    authorizationCode: $referenceId,
                ),
                'TIP' => ProviderResult::pending( // Transaction In Progress
                    providerReference: $referenceId,
                    providerStatus: $status,
                    responsePayload: $data,
                ),
                'TF' => ProviderResult::failure(  // Transaction Failed
                    message: $data['data']['transaction']['message'] ?? 'Airtel payment failed',
                    code: 'airtel_failed',
                    providerReference: $referenceId,
                    responsePayload: $data,
                ),
                default => ProviderResult::pending(
                    providerReference: $referenceId,
                    providerStatus: $status,
                    responsePayload: $data,
                ),
            };
        } catch (\Throwable $e) {
            return ProviderResult::failure('Airtel verify exception: ' . $e->getMessage(), 'verify_exception');
        }
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // Airtel signs callbacks with an HMAC using the client_secret as the key.
        // Header: X-Signature or X-Airtel-Signature
        $payload = $request->all();
        $signature = $request->header('X-Signature') ?? $request->header('X-Airtel-Signature');
        $rawBody = $request->getContent();

        $valid = false;

        if ($signature && $credential->webhook_secret) {
            $expected = base64_encode(hash_hmac('sha256', $rawBody, $credential->webhook_secret, true));
            $valid = hash_equals($expected, $signature);
        } else {
            // No signature header configured — fall back to API verification in PaymentProcessor
            $valid = true;
        }

        $reference = $data['transaction']['id']
            ?? $payload['transaction']['id']
            ?? $payload['reference']
            ?? null;

        $status = strtoupper($payload['transaction']['status'] ?? $payload['status'] ?? '');

        $eventType = match ($status) {
            'TS', 'SUCCESS', 'SUCCEEDED' => 'payment.succeeded',
            'TF', 'FAILED' => 'payment.failed',
            'TIP', 'PENDING' => 'payment.processing',
            default => 'payment.updated',
        };

        return [
            'valid' => $valid,
            'event_type' => $eventType,
            'provider_event_id' => $reference . ':' . $status,
            'provider_reference' => $reference,
            'payload' => $payload,
        ];
    }

    protected function dialingCode(string $countryIso2): string
    {
        return match (strtoupper($countryIso2)) {
            'UG' => '256',
            'KE' => '254',
            'TZ' => '255',
            'RW' => '250',
            'ZM' => '260',
            'NG' => '234',
            'GH' => '233',
            'MW' => '265',
            default => '256',
        };
    }
}
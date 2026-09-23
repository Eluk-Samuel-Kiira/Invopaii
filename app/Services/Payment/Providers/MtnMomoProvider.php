<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MtnMomoProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://sandbox.momodeveloper.mtn.com',
        'live' => 'https://proxy.momoapi.mtn.com',
    ];

    /**
     * MTN expects X-Target-Environment to be either "sandbox" or the country code
     * (e.g. "mtnuganda", "mtnrwanda"). Set per credential via `extra.target_environment`.
     */
    protected function targetEnvironment(string $mode, ?ProviderCredential $credential = null): string
    {
        if ($credential && !empty($credential->extra['target_environment'])) {
            return $credential->extra['target_environment'];
        }
        return $mode === 'live' ? 'mtnuganda' : 'sandbox';
    }

    /**
     * Obtain an OAuth access token for the given credential.
     * Cached for 50 minutes (MTN tokens expire after 60).
     */
    protected function accessToken(string $mode, ProviderCredential $credential): string
    {
        $cacheKey = 'mtn_token_' . $credential->id . '_' . $mode;

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($mode, $credential) {
            $response = $this->http($mode, [
                'Authorization' => 'Basic ' . base64_encode(
                    ($credential->extra['api_user'] ?? '') . ':' . ($credential->secret_key ?? '')
                ),
            ])->post('/collection/token/', [
                'grant_type' => 'client_credentials',
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'MTN OAuth failed: ' . $response->status() . ' ' . $response->body()
                );
            }

            $data = $response->json();
            $token = $data['access_token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('MTN OAuth returned no access_token.');
            }

            return $token;
        });
    }

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        $payment = $attempt->payment;
        $mode = $attempt->mode;

        if ($payment->payment_method_type !== 'mobile_money') {
            return ProviderResult::failure('MTN MoMo only supports mobile_money payments.', 'unsupported_method');
        }

        // The MSISDN is stored on the customer's mobile money payment method, or the payment itself
        $msisdn = $credential->extra['test_msisdn']
            ?? $payment->paymentMethod?->msisdn
            ?? $payment->metadata['msisdn']
            ?? null;

        if (!$msisdn) {
            return ProviderResult::failure('No mobile number provided for MTN MoMo charge.', 'missing_msisdn');
        }

        try {
            $token = $this->accessToken($mode, $credential);
        } catch (\Throwable $e) {
            return ProviderResult::failure('MTN auth failed: ' . $e->getMessage(), 'auth_failed');
        }

        // MTN requires a UUIDv4 reference — this is the request ID that identifies the charge
        $referenceId = (string) Str::uuid();
        $currency = $payment->currency; // MTN expects ISO 4217 (e.g. "UGX", "EUR")
        $amount = number_format($payment->amount / 100, 2, '.', ''); // MTN wants decimal
        // For zero-decimal currencies like UGX, amount stays as-is:
        if (in_array($currency, ['UGX', 'RWF'], true)) {
            $amount = (string) $payment->amount;
        }

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'externalId' => $payment->reference ?? $payment->public_id,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => preg_replace('/\D/', '', $msisdn),
            ],
            'payerMessage' => $payment->description ?? 'Payment',
            'payeeNote' => $payment->description ?? 'Payment',
        ];

        try {
            $response = $this->http($mode, [
                'Authorization' => 'Bearer ' . $token,
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => $this->targetEnvironment($mode, $credential),
                'Ocp-Apim-Subscription-Key' => $credential->public_key,
                'X-Callback-Url' => $payment->return_url ?? rtrim(config('app.url'), '/') . '/webhooks/mtn_momo',
            ])->post('/collection/v1_0/requesttopay', $payload);

            // MTN returns 202 Accepted — the actual payment result arrives via callback
            if ($response->status() === 202) {
                return ProviderResult::pending(
                    providerReference: $referenceId,
                    providerStatus: 'PENDING',
                    providerMessage: 'MTN MoMo charge initiated. Awaiting customer authorization.',
                    responsePayload: $payload,
                    nextActionType: 'wait_for_push',
                    nextAction: [
                        'message' => 'A payment prompt was sent to the customer\'s phone.',
                        'reference_id' => $referenceId,
                    ],
                );
            }

            // Any other status is a failure
            $data = $response->json() ?? [];
            return ProviderResult::failure(
                message: $data['message'] ?? 'MTN charge rejected',
                code: $data['code'] ?? 'mtn_error_' . $response->status(),
                providerReference: $referenceId,
                responsePayload: $data,
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ProviderResult::failure('MTN connection error: ' . $e->getMessage(), 'connection_error');
        } catch (\Throwable $e) {
            return ProviderResult::failure('MTN charge exception: ' . $e->getMessage(), 'provider_exception');
        }
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        $referenceId = $payment->provider_reference;
        if (!$referenceId) {
            return ProviderResult::failure('No MTN reference to verify.', 'no_reference');
        }

        try {
            $token = $this->accessToken($payment->mode, $credential);

            $response = $this->http($payment->mode, [
                'Authorization' => 'Bearer ' . $token,
                'X-Target-Environment' => $this->targetEnvironment($payment->mode, $credential),
                'Ocp-Apim-Subscription-Key' => $credential->public_key,
            ])->get("/collection/v1_0/requesttopay/{$referenceId}");

            if (!$response->successful()) {
                return ProviderResult::failure(
                    'MTN verify failed: ' . $response->status(),
                    'verify_failed',
                    providerReference: $referenceId,
                );
            }

            $data = $response->json();
            $status = $data['status'] ?? 'UNKNOWN';

            return match ($status) {
                'SUCCESSFUL' => ProviderResult::success(
                    providerReference: $referenceId,
                    providerStatus: $status,
                    providerMessage: $data['reason'] ?? 'Payment successful',
                    responsePayload: $data,
                    authorizationCode: $referenceId,
                ),
                'PENDING' => ProviderResult::pending(
                    providerReference: $referenceId,
                    providerStatus: $status,
                    responsePayload: $data,
                ),
                'FAILED', 'REJECTED', 'TIMEOUT' => ProviderResult::failure(
                    message: $data['reason'] ?? 'MTN payment ' . $status,
                    code: 'mtn_' . strtolower($status),
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
            return ProviderResult::failure('MTN verify exception: ' . $e->getMessage(), 'verify_exception');
        }
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        // MTN webhooks carry X-Reference-Id (the charge reference) as a header
        // and the event body contains {"status": "SUCCESSFUL|FAILED", "externalId": "...", "reason": "..."}

        $payload = $request->all();
        $referenceId = $request->header('X-Reference-Id') ?? ($payload['referenceId'] ?? null);

        // MTN doesn't sign webhooks with HMAC. Instead, we verify by fetching the charge
        // status directly from the API (verifyPayment). Treat the webhook as a trigger.
        $eventType = match (strtoupper($payload['status'] ?? '')) {
            'SUCCESSFUL' => 'payment.succeeded',
            'FAILED', 'REJECTED', 'TIMEOUT' => 'payment.failed',
            'PENDING' => 'payment.processing',
            default => 'payment.updated',
        };

        return [
            'valid' => true, // signature verification is via API round-trip in PaymentProcessor
            'event_type' => $eventType,
            'provider_event_id' => $referenceId . ':' . strtoupper($payload['status'] ?? ''),
            'provider_reference' => $referenceId,
            'payload' => $payload,
        ];
    }
}
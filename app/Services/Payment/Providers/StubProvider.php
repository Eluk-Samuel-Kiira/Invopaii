<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;

class StubProvider extends AbstractProvider
{
    protected array $baseUrls = [
        'test' => 'https://stub.local',
        'live' => 'https://stub.local',
    ];

    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult
    {
        // Fake processing delay
        usleep(100000); // 100ms

        // Always succeed unless the payment reference contains "fail"
        $ref = $attempt->payment->reference ?? '';

        if (str_contains(strtolower($ref), 'fail')) {
            return ProviderResult::failure(
                message: 'Stub provider: forced failure',
                code: 'stub_failure',
                providerReference: 'stub_' . uniqid(),
            );
        }

        return ProviderResult::success(
            providerReference: 'stub_' . uniqid(),
            providerStatus: 'succeeded',
            providerMessage: 'Stub charge succeeded',
            responsePayload: ['stub' => true, 'amount' => $attempt->amount],
            acquirerReference: 'ARN' . random_int(100000000, 999999999),
            authorizationCode: 'AUTH' . random_int(100000, 999999),
        );
    }

    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult
    {
        usleep(100000);

        return ProviderResult::success(
            providerReference: 'stub_refund_' . uniqid(),
            providerStatus: 'refunded',
            providerMessage: 'Stub refund succeeded',
        );
    }

    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        return [
            'valid' => true,
            'event_type' => $request->input('type', 'stub.event'),
            'provider_event_id' => $request->input('id'),
            'provider_reference' => $request->input('reference'),
            'payload' => $request->all(),
        ];
    }

    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        return ProviderResult::success(
            providerReference: $payment->provider_reference,
            providerStatus: 'succeeded',
        );
    }
}
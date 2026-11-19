<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

abstract class AbstractProvider implements ProviderInterface
{
    /** @var array<string, string> */
    protected array $baseUrls = [];

    protected function baseUrl(string $mode): string
    {
        return $this->baseUrls[$mode] ?? throw new \RuntimeException(
            "No base URL configured for mode [{$mode}] in " . static::class
        );
    }

    /**
     * Convenience for making JSON requests to the provider.
     */
    protected function http(string $mode, array $headers = [], int $timeout = 30)
    {
        return Http::withHeaders(array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $headers))
            ->timeout($timeout)
            ->baseUrl($this->baseUrl($mode));
    }

    /**
     * Default: refuse refunds unless the child class overrides.
     */
    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult
    {
        return ProviderResult::failure('Refunds are not supported by this provider.');
    }

    /**
     * Default: refuse manual capture.
     */
    public function capture(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        return ProviderResult::failure('Manual capture is not supported by this provider.');
    }

    /**
     * Default: refuse cancel.
     */
    public function cancel(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        return ProviderResult::failure('Cancellation is not supported by this provider.');
    }

    /**
     * Default: refuse webhooks until a provider implements this.
     */
    public function verifyWebhook(Request $request, ProviderCredential $credential): array
    {
        return [
            'valid' => false,
            'event_type' => null,
            'provider_event_id' => null,
            'provider_reference' => null,
            'payload' => $request->all(),
        ];
    }

    /**
     * Default: no polling.
     */
    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult
    {
        return ProviderResult::failure('Payment verification is not supported by this provider.');
    }
}
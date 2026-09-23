<?php

namespace App\Services\Payment\Providers;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use Illuminate\Http\Request;

interface ProviderInterface
{
    /**
     * Initiate a charge against the provider.
     */
    public function charge(PaymentAttempt $attempt, ProviderCredential $credential): ProviderResult;

    /**
     * Refund a previously successful payment (full or partial).
     */
    public function refund(Payment $payment, int $amount, ProviderCredential $credential, ?string $reason = null): ProviderResult;

    /**
     * Capture a previously authorized payment.
     */
    public function capture(Payment $payment, ProviderCredential $credential): ProviderResult;

    /**
     * Cancel/void an authorization before capture.
     */
    public function cancel(Payment $payment, ProviderCredential $credential): ProviderResult;

    /**
     * Verify an inbound webhook's signature and return the normalised payload.
     * Return ['valid' => bool, 'event_type' => string, 'provider_event_id' => string, 'provider_reference' => string, 'payload' => array]
     */
    public function verifyWebhook(Request $request, ProviderCredential $credential): array;

    /**
     * Poll the provider for a payment's current status.
     * Used when webhooks aren't available or are delayed.
     */
    public function verifyPayment(Payment $payment, ProviderCredential $credential): ProviderResult;
}
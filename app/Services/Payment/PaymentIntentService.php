<?php

namespace App\Services\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentProvider;
use Illuminate\Support\Facades\DB;

class PaymentIntentService
{
    /**
     * Create a payment intent. Returns the Payment record.
     *
     * @throws \RuntimeException if no provider can handle the payment
     */
    public static function create(Company $company, array $data): Payment
    {
        $mode = $data['mode'] ?? 'test';
        $currency = strtoupper($data['currency'] ?? $company->default_currency ?? 'USD');
        $amount = (int) $data['amount'];

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        // Idempotency: if a key is provided and already seen, return the existing payment
        if (!empty($data['idempotency_key'])) {
            $existing = Payment::where('company_id', $company->id)
                ->where('mode', $mode)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // Route
        [$provider, $fallback] = ProviderRouter::route([
            'payment_method' => $data['payment_method_type'] ?? null,
            'country_code' => $data['customer_country'] ?? null,
            'currency' => $currency,
            'card_brand' => $data['card_brand'] ?? null,
            'amount' => $amount,
            'company_id' => $company->id,
            'mode' => $mode,
        ]);

        if (!$provider) {
            throw new \RuntimeException('No payment provider is available for this payment.');
        }

        $customer = null;
        if (!empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }

        return DB::transaction(function () use ($company, $data, $mode, $currency, $amount, $provider, $customer) {
            $payment = $company->payments()->create([
                'mode' => $mode,
                'source' => $data['source'] ?? 'api',
                'customer_id' => $customer?->id,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_link_id' => $data['payment_link_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'checkout_session_id' => $data['checkout_session_id'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,

                'currency' => $currency,
                'amount' => $amount,
                'amount_captured' => 0,
                'amount_refunded' => 0,

                'status' => 'requires_payment_method',
                'capture_method' => $data['capture_method'] ?? 'automatic',
                'confirmation_method' => $data['confirmation_method'] ?? 'automatic',

                'payment_method_type' => $data['payment_method_type'] ?? null,
                'card_brand' => $data['card_brand'] ?? null,
                'card_last_four' => $data['card_last_four'] ?? null,
                'card_bin' => $data['card_bin'] ?? null,
                'card_country' => $data['card_country'] ?? null,
                'mobile_network' => $data['mobile_network'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,

                'payment_provider_id' => $provider->id,

                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'statement_descriptor' => $data['statement_descriptor'] ?? null,
                'receipt_email' => $data['receipt_email'] ?? $customer?->email,
                'return_url' => $data['return_url'] ?? null,

                'ip_address' => $data['ip_address'] ?? request()->ip(),
                'user_agent' => $data['user_agent'] ?? request()->userAgent(),
                'customer_country' => $data['customer_country'] ?? $customer?->country_code,
                'idempotency_key' => $data['idempotency_key'] ?? null,

                'expires_at' => $data['expires_at'] ?? now()->addHours(24),
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Move to requires_confirmation — the payment's shape is now known
            PaymentStateMachine::transition($payment, 'requires_confirmation');

            return $payment->fresh();
        });
    }

    /**
     * Create a payment and immediately process it. Common one-shot flow.
     */
    public static function createAndProcess(Company $company, array $data): Payment
    {
        $payment = self::create($company, $data);

        return PaymentProcessor::process($payment);
    }
}
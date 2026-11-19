<?php

namespace App\Services\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\RoutingRule;

class ProviderRouter
{
    /**
     * Pick the best provider for a payment. Returns [provider, fallback].
     * Falls back to any active provider supporting the method if no rule matches.
     */
    public static function route(array $attributes): array
    {
        $method = $attributes['payment_method'] ?? null;
        $country = $attributes['country_code'] ?? null;
        $currency = $attributes['currency'] ?? null;
        $brand = $attributes['card_brand'] ?? null;
        $amount = $attributes['amount'] ?? null;
        $companyId = $attributes['company_id'] ?? null;
        $mode = $attributes['mode'] ?? 'test';

        // 1. Try routing rules
        $rules = RoutingRule::matchFor([
            'payment_method' => $method,
            'country_code' => $country,
            'currency' => $currency,
            'card_brand' => $brand,
            'amount' => $amount,
            'company_id' => $companyId,
            'mode' => $mode,
        ]);

        foreach ($rules as $rule) {
            $provider = $rule->provider;

            if (!$provider || !$provider->is_active) continue;
            if ($method && !$provider->supportsMethod($method)) continue;
            if ($country && !$provider->supportsCountry($country)) continue;
            if ($currency && !$provider->supportsCurrency($currency)) continue;
            if ($mode === 'live' && !$provider->supports_test_mode && !app()->environment('production')) continue;
            if (!$provider->getUsableCredentialFor($companyId, $mode)) continue;

            $fallback = $rule->fallbackProvider;
            if ($fallback && (!$fallback->is_active || !$fallback->getUsableCredentialFor($companyId, $mode))) {
                $fallback = null;
            }

            return [$provider, $fallback];
        }

        // 2. No rule matched — fall back to any capable active provider ordered by priority
        $query = PaymentProvider::active()->orderBy('priority');

        if ($method) {
            $query->where(function ($q) use ($method) {
                $q->whereNull('supported_methods')
                  ->orWhereJsonContains('supported_methods', $method);
            });
        }
        if ($currency) {
            $query->where(function ($q) use ($currency) {
                $q->whereNull('supported_currencies')
                  ->orWhereJsonContains('supported_currencies', $currency);
            });
        }
        if ($country) {
            $query->where(function ($q) use ($country) {
                $q->whereNull('supported_countries')
                  ->orWhereJsonContains('supported_countries', $country);
            });
        }

        $providers = $query->get();

        foreach ($providers as $provider) {
            if (!$provider->getUsableCredentialFor($companyId, $mode)) continue;
            return [$provider, null];
        }

        return [null, null];
    }

    /**
     * Pick the provider for a payment model.
     */
    public static function routePayment(Payment $payment): array
    {
        return self::route([
            'payment_method' => $payment->payment_method_type,
            'country_code' => $payment->customer_country,
            'currency' => $payment->currency,
            'card_brand' => $payment->card_brand,
            'amount' => $payment->amount,
            'company_id' => $payment->company_id,
            'mode' => $payment->mode,
        ]);
    }

    /**
     * Resolve a provider's service class by its code.
     */
    public static function resolveProviderClass(string $code): ?string
    {
        $map = [
            'stub'         => \App\Services\Payment\Providers\StubProvider::class,
            'mtn_momo'     => \App\Services\Payment\Providers\MtnMomoProvider::class,
            'airtel_money' => \App\Services\Payment\Providers\AirtelMoneyProvider::class,
            'mpesa'        => \App\Services\Payment\Providers\MpesaProvider::class,
            'flutterwave'  => \App\Services\Payment\Providers\FlutterwaveProvider::class,
            'dpo'          => \App\Services\Payment\Providers\DpoProvider::class,
            'paypal'       => \App\Services\Payment\Providers\PaypalProvider::class,
            'pesapal' => \App\Services\Payment\Providers\PesapalProvider::class,
        ];

        return $map[$code] ?? null;
    }
}
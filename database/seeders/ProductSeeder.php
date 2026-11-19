<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies — skipping catalog seeder.');
            return;
        }

        foreach ($companies as $company) {
            // Skip if catalog already seeded for this company
            if ($company->products()->exists()) continue;

            $mode = $company->live_mode_enabled ? 'live' : 'test';
            $currency = $company->default_currency ?: 'USD';
            $countryCode = $company->country?->iso2;

            /* ── Tax rates ── */
            $company->taxRates()->create(array_merge($this->ids('txr_'), [
                'mode' => $mode,
                'display_name' => 'VAT',
                'percentage' => 18.000,
                'tax_type' => 'vat',
                'country_code' => $countryCode,
                'is_active' => true,
            ]));

            $company->taxRates()->create(array_merge($this->ids('txr_'), [
                'mode' => $mode,
                'display_name' => 'Withholding tax',
                'percentage' => 6.000,
                'tax_type' => 'withholding',
                'country_code' => $countryCode,
                'is_active' => true,
            ]));

            /* ── Products ── */
            $consulting = $company->products()->create(array_merge($this->ids('prod_'), [
                'mode' => $mode,
                'name' => 'Consulting Hour',
                'description' => 'Strategy and technical consulting, billed per hour',
                'sku' => 'CONSULT-HR',
                'unit_label' => 'hour',
                'is_active' => true,
                'is_shippable' => false,
            ]));

            $support = $company->products()->create(array_merge($this->ids('prod_'), [
                'mode' => $mode,
                'name' => 'Monthly Support Retainer',
                'description' => 'Ongoing support with a fixed monthly fee',
                'sku' => 'SUPPORT-MONTHLY',
                'unit_label' => 'month',
                'is_active' => true,
            ]));

            $setup = $company->products()->create(array_merge($this->ids('prod_'), [
                'mode' => $mode,
                'name' => 'Onboarding & Setup',
                'description' => 'One-time setup fee',
                'sku' => 'SETUP-001',
                'is_active' => true,
            ]));

            /* ── Prices ── */
            $consulting->prices()->create(array_merge($this->ids('price_'), [
                'company_id' => $company->id,
                'mode' => $mode,
                'nickname' => 'Standard rate',
                'currency' => $currency,
                'unit_amount' => 50000,
                'billing_scheme' => 'per_unit',
                'type' => 'one_time',
                'is_active' => true,
            ]));

            $support->prices()->create(array_merge($this->ids('price_'), [
                'company_id' => $company->id,
                'mode' => $mode,
                'nickname' => 'Retainer',
                'currency' => $currency,
                'unit_amount' => 250000,
                'billing_scheme' => 'per_unit',
                'type' => 'recurring',
                'recurring_interval' => 'month',
                'recurring_interval_count' => 1,
                'is_active' => true,
            ]));

            $setup->prices()->create(array_merge($this->ids('price_'), [
                'company_id' => $company->id,
                'mode' => $mode,
                'currency' => $currency,
                'unit_amount' => 100000,
                'billing_scheme' => 'per_unit',
                'type' => 'one_time',
                'is_active' => true,
            ]));

            /* ── Discounts ── */
            $company->discounts()->create(array_merge($this->ids('disc_'), [
                'mode' => $mode,
                'code' => 'WELCOME10',
                'name' => 'Welcome offer',
                'type' => 'percentage',
                'percent_off' => 10.000,
                'duration' => 'once',
                'max_redemptions' => 100,
                'is_active' => true,
            ]));

            $company->discounts()->create(array_merge($this->ids('disc_'), [
                'mode' => $mode,
                'code' => 'FLAT500',
                'name' => 'Flat 500 off',
                'type' => 'fixed_amount',
                'amount_off' => 50000,
                'currency' => $currency,
                'duration' => 'once',
                'minimum_order_amount' => 100000,
                'is_active' => true,
            ]));
        }

        $this->command->info('✓ Catalog seeded for ' . $companies->count() . ' companies.');
    }

    /**
     * Generate a UUID + prefixed public_id for a model create.
     */
    private function ids(string $prefix): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'public_id' => $prefix . Str::lower(Str::random(24)),
        ];
    }

    /**
     * For tables that only have a `uuid` column (no `public_id`).
     */
    private function uuidOnly(): array
    {
        return ['uuid' => (string) Str::uuid()];
    }
}
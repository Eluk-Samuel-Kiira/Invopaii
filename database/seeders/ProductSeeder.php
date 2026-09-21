<?php

namespace Database\Seeders;

use App\Models\Catalog\Discount;
use App\Models\Catalog\Price;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxRate;
use App\Models\Company\Company;
use Illuminate\Database\Seeder;

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

            /* ── Tax rates ── */
            $vat = $company->taxRates()->create([
                'mode' => $mode,
                'display_name' => 'VAT',
                'percentage' => 18.000,
                'tax_type' => 'vat',
                'country_code' => $company->country?->iso2,
                'is_active' => true,
            ]);

            $withholding = $company->taxRates()->create([
                'mode' => $mode,
                'display_name' => 'Withholding tax',
                'percentage' => 6.000,
                'tax_type' => 'withholding',
                'country_code' => $company->country?->iso2,
                'is_active' => true,
            ]);

            /* ── Products + prices ── */
            $consulting = $company->products()->create([
                'mode' => $mode,
                'name' => 'Consulting Hour',
                'description' => 'Strategy and technical consulting, billed per hour',
                'sku' => 'CONSULT-HR',
                'unit_label' => 'hour',
                'is_active' => true,
                'is_shippable' => false,
            ]);

            $consulting->prices()->createMany([
                [
                    'company_id' => $company->id,
                    'mode' => $mode,
                    'nickname' => 'Standard rate',
                    'currency' => $currency,
                    'unit_amount' => 50000,
                    'billing_scheme' => 'per_unit',
                    'type' => 'one_time',
                    'is_active' => true,
                ],
            ]);

            $support = $company->products()->create([
                'mode' => $mode,
                'name' => 'Monthly Support Retainer',
                'description' => 'Ongoing support with a fixed monthly fee',
                'sku' => 'SUPPORT-MONTHLY',
                'unit_label' => 'month',
                'is_active' => true,
            ]);

            $support->prices()->createMany([
                [
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
                ],
            ]);

            $setup = $company->products()->create([
                'mode' => $mode,
                'name' => 'Onboarding & Setup',
                'description' => 'One-time setup fee',
                'sku' => 'SETUP-001',
                'is_active' => true,
            ]);

            $setup->prices()->create([
                'company_id' => $company->id,
                'mode' => $mode,
                'currency' => $currency,
                'unit_amount' => 100000,
                'billing_scheme' => 'per_unit',
                'type' => 'one_time',
                'is_active' => true,
            ]);

            /* ── Discounts ── */
            $company->discounts()->createMany([
                [
                    'mode' => $mode,
                    'code' => 'WELCOME10',
                    'name' => 'Welcome offer',
                    'type' => 'percentage',
                    'percent_off' => 10.000,
                    'duration' => 'once',
                    'max_redemptions' => 100,
                    'is_active' => true,
                ],
                [
                    'mode' => $mode,
                    'code' => 'FLAT500',
                    'name' => 'Flat 500 off',
                    'type' => 'fixed_amount',
                    'amount_off' => 50000,
                    'currency' => $currency,
                    'duration' => 'once',
                    'minimum_order_amount' => 100000,
                    'is_active' => true,
                ],
            ]);
        }

        $this->command->info('✓ Catalog seeded for ' . $companies->count() . ' companies.');
    }
}
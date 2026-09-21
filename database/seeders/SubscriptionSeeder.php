<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Payment\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->with('customers')->limit(3)->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies — skipping subscription seeder.');
            return;
        }

        $count = 0;

        foreach ($companies as $company) {
            $customers = $company->customers()->limit(2)->get();

            if ($customers->isEmpty()) continue;

            foreach ($customers as $index => $customer) {
                // Skip if this customer already has a subscription
                if ($company->subscriptions()->where('customer_id', $customer->id)->exists()) {
                    continue;
                }

                $mode = $company->live_mode_enabled ? 'live' : 'test';
                $currency = $company->default_currency ?: 'USD';
                $start = now();

                $isTrialing = ($index === 1);

                $sub = $company->subscriptions()->create([
                    'customer_id' => $customer->id,
                    'mode' => $mode,
                    'description' => $isTrialing ? 'Monthly hosting — trial' : 'Monthly hosting plan',
                    'currency' => $currency,
                    'status' => $isTrialing ? 'trialing' : 'active',
                    'collection_method' => 'charge_automatically',
                    'billing_interval' => 'month',
                    'billing_interval_count' => 1,
                    'current_period_start' => $start,
                    'current_period_end' => $start->copy()->addMonth(),
                    'trial_start' => $isTrialing ? $start : null,
                    'trial_end' => $isTrialing ? $start->copy()->addDays(14) : null,
                    'started_at' => $start,
                    'next_billing_at' => $isTrialing ? $start->copy()->addDays(14) : $start->copy()->addMonth(),
                ]);

                $sub->items()->create([
                    'name' => 'Monthly Hosting',
                    'unit_amount' => 50000,
                    'currency' => $currency,
                    'quantity' => 1,
                ]);

                $count++;
            }
        }

        $this->command->info("✓ Seeded {$count} subscriptions.");
    }
}
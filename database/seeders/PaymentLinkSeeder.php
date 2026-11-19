<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Payment\PaymentLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentLinkSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->limit(3)->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies — skipping payment link seeder.');
            return;
        }

        $templates = [
            [
                'title' => 'Consulting — March 2026',
                'description' => 'Monthly consulting retainer',
                'amount_type' => 'fixed',
                'amount' => 500000,
                'currency' => 'UGX',
                'usage_type' => 'multi_use',
                'collect_customer_name' => true,
                'collect_email' => true,
                'collect_phone' => false,
            ],
            [
                'title' => 'Website Deposit',
                'description' => '50% deposit to start your project',
                'amount_type' => 'fixed',
                'amount' => 1500000,
                'currency' => 'UGX',
                'usage_type' => 'single_use',
                'collect_customer_name' => true,
                'collect_email' => true,
            ],
            [
                'title' => 'Support Our Work',
                'description' => 'Choose any amount to support our mission',
                'amount_type' => 'customer_chooses',
                'minimum_amount' => 5000,
                'maximum_amount' => 500000,
                'currency' => 'UGX',
                'usage_type' => 'multi_use',
                'collect_customer_name' => true,
                'collect_email' => true,
            ],
        ];

        $count = 0;

        foreach ($companies as $company) {
            foreach ($templates as $tpl) {
                if ($company->paymentLinks()->where('title', $tpl['title'])->exists()) continue;

                $mode = $company->live_mode_enabled ? 'live' : 'test';

                $company->paymentLinks()->create(array_merge($tpl, [
                    'uuid' => (string) Str::uuid(),
                    'public_id' => 'plink_' . Str::lower(Str::random(24)),
                    'slug' => $this->uniqueSlug($mode, $tpl['title']),
                    'mode' => $mode,
                    'created_by_id' => $company->owner_id,
                    'after_completion' => 'hosted_confirmation',
                    'send_receipt' => true,
                    'status' => 'active',
                    'expires_at' => now()->addDays(90),
                ]));

                $count++;
            }
        }

        $this->command->info("✓ Seeded {$count} payment links.");
    }

    /**
     * Slug must be unique per (mode) GLOBALLY — the DB index is
     * payment_links_mode_slug_unique on (mode, slug), not per company.
     */
    private function uniqueSlug(string $mode, string $title): string
    {
        $base = Str::slug($title) ?: 'link';
        $slug = $base;
        $i = 2;

        while (PaymentLink::withTrashed()->where('mode', $mode)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
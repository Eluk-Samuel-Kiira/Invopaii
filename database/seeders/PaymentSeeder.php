<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\PaymentProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command->warn('No companies. Skipping payments seeder.');
            return;
        }

        $customer = $company->customers()->first();
        $stubProvider = PaymentProvider::where('code', 'stub')->first();

        if (!$stubProvider) {
            $this->command->warn('No stub provider. Skipping payments seeder.');
            return;
        }

        $templates = [
            [
                'status' => 'succeeded',
                'amount' => 75000,
                'method' => 'mobile_money',
                'reference' => 'DEMO-001',
                'attempts' => 1,
                'attempt_status' => 'succeeded',
            ],
            [
                'status' => 'succeeded',
                'amount' => 250000,
                'method' => 'card',
                'reference' => 'DEMO-002',
                'attempts' => 1,
                'attempt_status' => 'succeeded',
            ],
            [
                'status' => 'failed',
                'amount' => 12000,
                'method' => 'mobile_money',
                'reference' => 'fail-001',
                'attempts' => 1,
                'attempt_status' => 'failed',
            ],
            [
                'status' => 'processing',
                'amount' => 50000,
                'method' => 'mobile_money',
                'reference' => 'DEMO-004',
                'attempts' => 1,
                'attempt_status' => 'pending',
            ],
            [
                'status' => 'requires_action',
                'amount' => 90000,
                'method' => 'mobile_money',
                'reference' => 'DEMO-005',
                'attempts' => 1,
                'attempt_status' => 'pending',
                'next_action' => ['type' => 'wait_for_push', 'message' => 'Enter PIN on phone'],
            ],
            [
                'status' => 'failed',
                'amount' => 30000,
                'method' => 'mobile_money',
                'reference' => 'DEMO-006',
                'attempts' => 2,
                'attempt_status' => 'failed',
            ],
            [
                'status' => 'cancelled',
                'amount' => 15000,
                'method' => 'mobile_money',
                'reference' => 'DEMO-007',
                'attempts' => 1,
                'attempt_status' => 'failed',
            ],
        ];

        $count = 0;

        foreach ($templates as $i => $tpl) {
            $existing = $company->payments()->where('reference', $tpl['reference'])->exists();
            if ($existing) continue;

            $mode = $i % 3 === 0 ? 'test' : 'live';
            $createdAt = now()->subDays(rand(1, 30));

            $payment = $company->payments()->create([
                'mode' => $mode,
                'source' => 'dashboard',
                'customer_id' => $customer?->id,
                'currency' => $company->default_currency ?: 'UGX',
                'amount' => $tpl['amount'],
                'amount_captured' => $tpl['status'] === 'succeeded' ? $tpl['amount'] : 0,
                'status' => $tpl['status'],
                'payment_method_type' => $tpl['method'],
                'payment_provider_id' => $stubProvider->id,
                'reference' => $tpl['reference'],
                'description' => 'Demo payment #' . ($i + 1),
                'receipt_email' => $customer?->email,
                'customer_country' => $customer?->country_code,
                'attempt_count' => $tpl['attempts'],
                'provider_reference' => $tpl['status'] === 'succeeded' ? 'stub_' . Str::random(12) : null,
                'provider_authorization_code' => $tpl['status'] === 'succeeded' ? 'AUTH' . rand(100000, 999999) : null,
                'acquirer_reference' => $tpl['status'] === 'succeeded' ? 'ARN' . rand(100000000, 999999999) : null,
                'next_action_type' => $tpl['next_action']['type'] ?? null,
                'next_action' => $tpl['next_action'] ?? null,
                'failure_code' => $tpl['status'] === 'failed' ? 'stub_failure' : null,
                'failure_message' => $tpl['status'] === 'failed' ? 'Stub provider: forced failure' : null,
                'succeeded_at' => $tpl['status'] === 'succeeded' ? $createdAt->copy()->addSeconds(5) : null,
                'failed_at' => $tpl['status'] === 'failed' ? $createdAt->copy()->addSeconds(3) : null,
                'cancelled_at' => $tpl['status'] === 'cancelled' ? $createdAt->copy()->addSeconds(2) : null,
                'captured_at' => $tpl['status'] === 'succeeded' ? $createdAt->copy()->addSeconds(5) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            for ($n = 1; $n <= $tpl['attempts']; $n++) {
                $isLast = $n === $tpl['attempts'];
                $attemptStatus = $isLast ? $tpl['attempt_status'] : 'failed';

                PaymentAttempt::create([
                    'payment_id' => $payment->id,
                    'company_id' => $company->id,
                    'payment_provider_id' => $stubProvider->id,
                    'mode' => $mode,
                    'attempt_number' => $n,
                    'operation' => 'charge',
                    'status' => $attemptStatus,
                    'amount' => $tpl['amount'],
                    'currency' => $payment->currency,
                    'provider_reference' => $attemptStatus === 'succeeded' ? 'stub_' . Str::random(12) : null,
                    'provider_status' => $attemptStatus,
                    'provider_message' => $attemptStatus === 'succeeded'
                        ? 'Stub charge succeeded'
                        : 'Stub provider: forced failure',
                    'response_payload' => ['stub' => true, 'amount' => $tpl['amount']],
                    'duration_ms' => rand(80, 500),
                    'is_retry' => $n > 1,
                    'failure_reason' => $attemptStatus === 'failed' ? 'Stub provider: forced failure' : null,
                    'started_at' => $createdAt->copy()->addSeconds($n * 2),
                    'completed_at' => $createdAt->copy()->addSeconds($n * 2 + 1),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $count++;
        }

        $this->command->info("✓ Seeded {$count} demo payments with attempts.");
    }
}
<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Payment\PaymentMethod;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->with('country')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found — skipping customer seeder.');
            return;
        }

        $demo = [
            // ─── Uganda / UGX ───
            [
                'name' => 'Amina Nakato',
                'email' => 'amina.nakato@example.test',
                'phone' => '+256772100001',
                'reference' => 'CUST-001',
                'country_code' => 'UG',
                'preferred_currency' => 'UGX',
                'lifetime_value' => 850000,
                'successful_payments_count' => 12,
                'mode' => 'live',
                'address' => [
                    'line1' => 'Plot 24 Ntinda Road',
                    'city' => 'Kampala',
                    'country_code' => 'UG',
                    'postal_code' => '256',
                    'is_default' => true,
                ],
                'card' => [
                    'card_brand' => 'visa',
                    'card_last_four' => '4242',
                    'card_bin' => '424242',
                    'card_exp_month' => 8,
                    'card_exp_year' => 2028,
                    'card_funding' => 'credit',
                    'card_country' => 'UG',
                    'provider' => 'internal',
                    'is_default' => true,
                ],
            ],
            [
                'name' => 'Joseph Ssekandi',
                'email' => 'joseph.ssekandi@example.test',
                'phone' => '+256701200002',
                'reference' => 'CUST-002',
                'country_code' => 'UG',
                'preferred_currency' => 'UGX',
                'lifetime_value' => 320000,
                'successful_payments_count' => 4,
                'mode' => 'live',
                'address' => [
                    'line1' => 'Entebbe Road, Plot 18',
                    'city' => 'Kampala',
                    'country_code' => 'UG',
                    'is_default' => true,
                ],
                'mobile' => [
                    'mobile_network' => 'mtn',
                    'msisdn_last_four' => '0002',
                    'provider' => 'internal',
                    'is_default' => true,
                ],
            ],
            // ─── Kenya / KES ───
            [
                'name' => 'Grace Wanjiru',
                'email' => 'grace.wanjiru@example.test',
                'phone' => '+254711300003',
                'reference' => 'KE-1001',
                'country_code' => 'KE',
                'preferred_currency' => 'KES',
                'lifetime_value' => 45000,
                'successful_payments_count' => 9,
                'mode' => 'live',
                'address' => [
                    'line1' => 'Westlands, Woodvale Grove',
                    'city' => 'Nairobi',
                    'country_code' => 'KE',
                    'postal_code' => '00100',
                    'is_default' => true,
                ],
                'card' => [
                    'card_brand' => 'mastercard',
                    'card_last_four' => '5555',
                    'card_bin' => '555555',
                    'card_exp_month' => 11,
                    'card_exp_year' => 2027,
                    'card_funding' => 'debit',
                    'card_country' => 'KE',
                    'provider' => 'internal',
                    'is_default' => true,
                ],
            ],
            [
                'name' => 'Peter Ochieng',
                'email' => 'peter.ochieng@example.test',
                'phone' => '+254722400004',
                'country_code' => 'KE',
                'preferred_currency' => 'KES',
                'lifetime_value' => 0,
                'successful_payments_count' => 0,
                'mode' => 'test',
                'is_blocked' => true,
                'blocked_reason' => 'Suspected fraudulent activity',
                'address' => [
                    'line1' => 'Mombasa Road',
                    'city' => 'Nairobi',
                    'country_code' => 'KE',
                    'is_default' => true,
                ],
            ],
            // ─── Nigeria / NGN ───
            [
                'name' => 'Chioma Okonkwo',
                'email' => 'chioma.okonkwo@example.test',
                'phone' => '+234803500005',
                'reference' => 'NG-CUST-501',
                'country_code' => 'NG',
                'preferred_currency' => 'NGN',
                'lifetime_value' => 25000000,
                'successful_payments_count' => 27,
                'mode' => 'live',
                'address' => [
                    'line1' => '12 Adeola Odeku Street, Victoria Island',
                    'city' => 'Lagos',
                    'state' => 'Lagos',
                    'country_code' => 'NG',
                    'is_default' => true,
                ],
                'card' => [
                    'card_brand' => 'visa',
                    'card_last_four' => '1881',
                    'card_bin' => '408408',
                    'card_exp_month' => 3,
                    'card_exp_year' => 2027,
                    'card_funding' => 'credit',
                    'card_country' => 'NG',
                    'provider' => 'internal',
                    'is_default' => true,
                ],
            ],
            [
                'name' => 'Emeka Nwosu',
                'email' => 'emeka.nwosu@example.test',
                'phone' => '+234806600006',
                'country_code' => 'NG',
                'preferred_currency' => 'NGN',
                'lifetime_value' => 8500000,
                'successful_payments_count' => 5,
                'disputed_payments_count' => 1,
                'mode' => 'live',
                'address' => [
                    'line1' => '7 Ozumba Mbadiwe Ave',
                    'city' => 'Lagos',
                    'country_code' => 'NG',
                    'is_default' => true,
                ],
            ],
            // ─── Test data only (test mode) ───
            [
                'name' => 'Test User',
                'email' => 'test.user@example.test',
                'phone' => '+155500007',
                'country_code' => 'US',
                'preferred_currency' => 'USD',
                'lifetime_value' => 0,
                'successful_payments_count' => 0,
                'mode' => 'test',
                'address' => [
                    'line1' => '123 Test Street',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'country_code' => 'US',
                    'postal_code' => '94105',
                    'is_default' => true,
                ],
                'card' => [
                    'card_brand' => 'visa',
                    'card_last_four' => '4242',
                    'card_bin' => '424242',
                    'card_exp_month' => 12,
                    'card_exp_year' => 2030,
                    'card_funding' => 'credit',
                    'card_country' => 'US',
                    'provider' => 'internal',
                    'is_default' => true,
                ],
            ],
        ];

        $count = 0;

        foreach ($companies as $company) {
            foreach ($demo as $template) {
                // Skip if customer already exists for this company
                if (!empty($template['email'])) {
                    $exists = $company->customers()
                        ->where('email', $template['email'])
                        ->where('mode', $template['mode'])
                        ->exists();

                    if ($exists) continue;
                }

                $customer = $company->customers()->create([
                    'mode' => $template['mode'],
                    'name' => $template['name'],
                    'email' => $template['email'] ?? null,
                    'phone' => $template['phone'] ?? null,
                    'reference' => $template['reference'] ?? null,
                    'country_code' => $template['country_code'] ?? null,
                    'preferred_currency' => $template['preferred_currency'] ?? null,
                    'lifetime_value' => $template['lifetime_value'] ?? 0,
                    'successful_payments_count' => $template['successful_payments_count'] ?? 0,
                    'disputed_payments_count' => $template['disputed_payments_count'] ?? 0,
                    'is_blocked' => $template['is_blocked'] ?? false,
                    'blocked_reason' => $template['blocked_reason'] ?? null,
                    'last_paid_at' => ($template['successful_payments_count'] ?? 0) > 0 ? now()->subDays(rand(1, 30)) : null,
                    'first_paid_at' => ($template['successful_payments_count'] ?? 0) > 0 ? now()->subMonths(rand(3, 24)) : null,
                ]);

                // Address
                if (!empty($template['address'])) {
                    $address = $customer->addresses()->create(array_merge(
                        ['type' => 'billing'],
                        $template['address']
                    ));
                    if (!empty($template['address']['is_default'])) {
                        $customer->update(['default_address_id' => $address->id]);
                    }
                }

                // Card
                if (!empty($template['card'])) {
                    $pm = $customer->paymentMethods()->create(array_merge(
                        [
                            'company_id' => $company->id,
                            'mode' => $template['mode'],
                            'type' => 'card',
                            'status' => 'active',
                            'is_reusable' => true,
                            'fingerprint' => hash_hmac('sha256', $company->id . ':' . ($template['card']['card_bin'] ?? '') . ($template['card']['card_last_four'] ?? ''), config('app.key')),
                            'last_used_at' => now()->subDays(rand(1, 14)),
                        ],
                        $template['card']
                    ));
                    if (!empty($template['card']['is_default'])) {
                        $customer->update(['default_payment_method_id' => $pm->id]);
                    }
                }

                // Mobile money
                if (!empty($template['mobile'])) {
                    $pm = $customer->paymentMethods()->create(array_merge(
                        [
                            'company_id' => $company->id,
                            'mode' => $template['mode'],
                            'type' => 'mobile_money',
                            'status' => 'active',
                            'is_reusable' => true,
                        ],
                        $template['mobile']
                    ));
                    if (!empty($template['mobile']['is_default']) && !$customer->default_payment_method_id) {
                        $customer->update(['default_payment_method_id' => $pm->id]);
                    }
                }

                $count++;
            }
        }

        $this->command->info("✓ Seeded {$count} customers with addresses and payment methods.");
    }
}
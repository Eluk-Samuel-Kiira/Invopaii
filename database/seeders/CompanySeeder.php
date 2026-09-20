<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Reference\Country;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $uganda  = Country::where('iso2', 'UG')->first();
        $kenya   = Country::where('iso2', 'KE')->first();
        $nigeria = Country::where('iso2', 'NG')->first();

        $owner = User::where('email', 'merchant@stardena.com')->first()
            ?? User::where('email', 'superadmin@lafab.com')->first();

        if (!$owner) {
            $this->command->warn('No suitable owner user found — skipping company seeder.');
            return;
        }

        $companies = [
            [
                'name' => 'Stardena Demo Store',
                'legal_name' => 'Stardena Demo Store Ltd',
                'slug' => 'stardena-demo',
                'email' => 'hello@stardena-demo.test',
                'support_email' => 'support@stardena-demo.test',
                'support_phone' => '+256700000100',
                'website' => 'https://stardena-demo.test',
                'brand_color' => '#6E3FE7',
                'country_id' => $uganda?->id,
                'business_type' => 'company',
                'industry' => 'E-commerce',
                'mcc' => '5999',
                'registration_number' => 'UG-REG-123456',
                'tax_identification_number' => 'TIN-100200300',
                'incorporated_on' => now()->subYears(2),
                'address_line1' => 'Plot 10, Kampala Road',
                'city' => 'Kampala',
                'state' => 'Central',
                'postal_code' => '256',
                'default_currency' => 'UGX',
                'settlement_currency' => 'UGX',
                'timezone' => 'Africa/Kampala',
                'statement_descriptor' => 'STARDENA DEMO',
                'status' => 'active',
                'kyb_status' => 'verified',
                'live_mode_enabled' => true,
                'charges_enabled' => true,
                'payouts_enabled' => true,
                'activated_at' => now()->subDays(30),
                'risk_level' => 'standard',
                'risk_score' => 20,
                'payout_schedule' => 'daily',
                'payout_delay_days' => 2,
                'reserve_percent' => 0,
                'reserve_hold_days' => 0,
                'owner_id' => $owner->id,
            ],
            [
                'name' => 'Nairobi Coffee Co',
                'legal_name' => 'Nairobi Coffee Company Limited',
                'slug' => 'nairobi-coffee',
                'email' => 'orders@nairobicoffee.test',
                'country_id' => $kenya?->id,
                'business_type' => 'company',
                'industry' => 'Food & Beverage',
                'mcc' => '5814',
                'default_currency' => 'KES',
                'settlement_currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'statement_descriptor' => 'NAIROBI COFFEE',
                'status' => 'in_review',
                'kyb_status' => 'pending',
                'live_mode_enabled' => false,
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'risk_level' => 'standard',
                'payout_schedule' => 'weekly',
                'payout_delay_days' => 3,
                'reserve_percent' => 5,
                'reserve_hold_days' => 90,
                'owner_id' => $owner->id,
            ],
            [
                'name' => 'Lagos Digital',
                'legal_name' => 'Lagos Digital Services Ltd',
                'slug' => 'lagos-digital',
                'email' => 'pay@lagosdigital.test',
                'country_id' => $nigeria?->id,
                'business_type' => 'company',
                'industry' => 'SaaS',
                'mcc' => '7372',
                'default_currency' => 'NGN',
                'settlement_currency' => 'NGN',
                'timezone' => 'Africa/Lagos',
                'statement_descriptor' => 'LAGOS DIGITAL',
                'status' => 'pending',
                'kyb_status' => 'unverified',
                'live_mode_enabled' => false,
                'charges_enabled' => false,
                'payouts_enabled' => false,
                'risk_level' => 'elevated',
                'payout_schedule' => 'manual',
                'payout_delay_days' => 7,
                'reserve_percent' => 10,
                'reserve_hold_days' => 180,
                'owner_id' => $owner->id,
            ],
        ];

        foreach ($companies as $data) {
            if (empty($data['country_id'])) {
                $this->command->warn("Skipping {$data['slug']} — country not found.");
                continue;
            }

            // Find existing company first so we don't regenerate uuid on update
            $existing = Company::where('slug', $data['slug'])->first();

            $payload = $data;

            if (!$existing) {
                // Only set uuid + public_id on insert — they're immutable
                $payload['uuid'] = (string) Str::uuid();
                $payload['public_id'] = 'acct_' . Str::lower(Str::random(24));
            }

            $company = Company::updateOrCreate(
                ['slug' => $data['slug']],
                $payload
            );

            // Attach owner to pivot (idempotent)
            $company->users()->syncWithoutDetaching([
                $owner->id => [
                    'role' => 'owner',
                    'can_access_live_mode' => true,
                    'status' => 'active',
                ],
            ]);
        }

        $this->command->info('✓ Seeded ' . Company::count() . ' companies.');
    }
}
<?php

namespace Database\Seeders;

use App\Models\Payment\FeeSchedule;
use Illuminate\Database\Seeder;

class FeeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            [
                'name' => 'Standard Uganda',
                'code' => 'standard_ug',
                'description' => 'Default rate card for Ugandan merchants',
                'is_default' => true,
                'is_active' => true,
                'rules' => [
                    ['fee_type' => 'processing', 'payment_method' => 'mobile_money', 'currency' => 'UGX', 'country_code' => 'UG', 'percentage' => 2.5, 'fixed_amount' => 500, 'minimum_fee' => 300, 'maximum_fee' => 10000, 'tax_percentage' => 0, 'priority' => 10],
                    ['fee_type' => 'processing', 'payment_method' => 'card', 'currency' => 'UGX', 'country_code' => 'UG', 'percentage' => 3.5, 'fixed_amount' => 200, 'minimum_fee' => 500, 'maximum_fee' => 50000, 'tax_percentage' => 0, 'priority' => 20],
                    ['fee_type' => 'refund', 'currency' => 'UGX', 'percentage' => 0, 'fixed_amount' => 1000, 'priority' => 100],
                ],
            ],
            [
                'name' => 'Standard Kenya',
                'code' => 'standard_ke',
                'description' => 'Default rate card for Kenyan merchants',
                'is_default' => false,
                'is_active' => true,
                'rules' => [
                    ['fee_type' => 'processing', 'payment_method' => 'mobile_money', 'currency' => 'KES', 'country_code' => 'KE', 'percentage' => 1.8, 'fixed_amount' => 0, 'minimum_fee' => 10, 'maximum_fee' => 5000, 'tax_percentage' => 0, 'priority' => 10],
                    ['fee_type' => 'processing', 'payment_method' => 'card', 'currency' => 'KES', 'country_code' => 'KE', 'percentage' => 2.9, 'fixed_amount' => 0, 'minimum_fee' => 20, 'maximum_fee' => 10000, 'tax_percentage' => 0, 'priority' => 20],
                ],
            ],
            [
                'name' => 'Enterprise',
                'code' => 'enterprise',
                'description' => 'Negotiated rates for high-volume merchants',
                'is_default' => false,
                'is_active' => true,
                'rules' => [
                    ['fee_type' => 'processing', 'payment_method' => 'mobile_money', 'percentage' => 1.5, 'fixed_amount' => 0, 'minimum_fee' => 100, 'maximum_fee' => 500000, 'tax_percentage' => 0, 'priority' => 10],
                    ['fee_type' => 'processing', 'payment_method' => 'card', 'percentage' => 2.0, 'fixed_amount' => 0, 'minimum_fee' => 200, 'maximum_fee' => 1000000, 'tax_percentage' => 0, 'priority' => 20],
                    ['fee_type' => 'payout', 'percentage' => 0, 'fixed_amount' => 500, 'priority' => 30],
                ],
            ],
        ];

        foreach ($schedules as $data) {
            $schedule = FeeSchedule::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_default' => $data['is_default'],
                    'is_active' => $data['is_active'],
                ]
            );

            // Replace rules on re-seed
            $schedule->rules()->delete();
            foreach ($data['rules'] as $rule) {
                $schedule->rules()->create($rule);
            }
        }

        $this->command->info('✓ Seeded ' . count($schedules) . ' fee schedules.');
    }
}
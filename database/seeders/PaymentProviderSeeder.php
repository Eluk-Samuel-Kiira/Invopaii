<?php

namespace Database\Seeders;

use App\Models\Payment\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'code' => 'mtn_momo',
                'name' => 'MTN Mobile Money',
                'type' => 'mobile_money',
                'supported_countries' => ['UG', 'RW', 'GH', 'CM', 'ZM'],
                'supported_currencies' => ['UGX', 'RWF', 'GHS', 'XAF', 'ZMW'],
                'supported_methods' => ['mobile_money'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => false,
                ],
                'priority' => 10,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/mtn_momo',
            ],
            [
                'code' => 'airtel_money',
                'name' => 'Airtel Money',
                'type' => 'mobile_money',
                'supported_countries' => ['UG', 'KE', 'TZ', 'RW', 'ZM', 'NG'],
                'supported_currencies' => ['UGX', 'KES', 'TZS', 'RWF', 'ZMW', 'NGN'],
                'supported_methods' => ['mobile_money'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => false,
                    'recurring' => false,
                ],
                'priority' => 20,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/airtel_money',
            ],
            [
                'code' => 'mpesa',
                'name' => 'M-PESA',
                'type' => 'mobile_money',
                'supported_countries' => ['KE', 'TZ', 'MZ', 'CD', 'GH'],
                'supported_currencies' => ['KES', 'TZS', 'MZN', 'CDF', 'GHS'],
                'supported_methods' => ['mobile_money'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => false,
                ],
                'priority' => 15,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/mpesa',
            ],
            [
                'code' => 'flutterwave',
                'name' => 'Flutterwave',
                'type' => 'aggregator',
                'supported_countries' => ['UG', 'KE', 'NG', 'GH', 'ZA', 'TZ', 'RW', 'ZM'],
                'supported_currencies' => ['UGX', 'KES', 'NGN', 'GHS', 'ZAR', 'TZS', 'RWF', 'USD', 'EUR', 'GBP'],
                'supported_methods' => ['card', 'mobile_money', 'bank_transfer', 'ussd'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => true,
                    'cards' => true,
                ],
                'priority' => 50,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/flutterwave',
            ],
            [
                'code' => 'dpo',
                'name' => 'DPO Pay',
                'type' => 'aggregator',
                'supported_countries' => ['UG', 'KE', 'TZ', 'RW', 'ZM', 'ZW', 'GH', 'NG'],
                'supported_currencies' => ['UGX', 'KES', 'TZS', 'RWF', 'ZMW', 'USD', 'EUR', 'GBP'],
                'supported_methods' => ['card', 'mobile_money', 'bank_transfer'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => true,
                    'cards' => true,
                ],
                'priority' => 60,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/dpo',
            ],
            [
                'code' => 'paypal',
                'name' => 'PayPal',
                'type' => 'wallet',
                'supported_countries' => [],
                'supported_currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD'],
                'supported_methods' => ['wallet', 'card'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => false,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => true,
                    'cards' => true,
                ],
                'priority' => 80,
                'supports_test_mode' => true,
                'is_active' => false,
                'webhook_path' => '/webhooks/paypal',
            ],
            [
                'code' => 'stub',
                'name' => 'Stub Provider (Testing)',
                'type' => 'aggregator',
                'supported_countries' => null,
                'supported_currencies' => null,
                'supported_methods' => ['card', 'mobile_money', 'bank_transfer', 'ussd', 'wallet'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => true,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => true,
                    'cards' => true,
                ],
                'priority' => 999,   // last resort
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/stub',
            ],
            [
                'code' => 'pesapal',
                'name' => 'Pesapal',
                'type' => 'aggregator',
                'supported_countries' => ['UG', 'KE', 'TZ', 'RW', 'ZM', 'MW', 'ZW'],
                'supported_currencies' => ['UGX', 'KES', 'TZS', 'RWF', 'ZMW', 'USD'],
                'supported_methods' => ['card', 'mobile_money', 'bank_transfer'],
                'capabilities' => [
                    'collections' => true,
                    'disbursements' => false,
                    'refunds' => true,
                    'partial_refunds' => true,
                    'recurring' => false,
                    'cards' => true,
                ],
                'priority' => 40,
                'supports_test_mode' => true,
                'is_active' => true,
                'webhook_path' => '/webhooks/pesapal',
            ],
        ];

        foreach ($providers as $data) {
            PaymentProvider::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('✓ Seeded ' . count($providers) . ' payment providers.');
    }
}
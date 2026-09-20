<?php

namespace Database\Seeders;

use App\Models\Reference\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            // ─────────────── Major settlement / presentment ───────────────
            [
                'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,        // $0.50
                'max_charge_amount' => 50000000,  // $500,000.00
            ],
            [
                'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,
                'max_charge_amount' => 50000000,
            ],
            [
                'code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,
                'max_charge_amount' => 50000000,
            ],

            // ─────────────── East Africa (core market) ───────────────
            [
                'code' => 'UGX', 'name' => 'Ugandan Shilling', 'symbol' => 'USh',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 1000,        // UGX 1,000
                'max_charge_amount' => 500000000,   // UGX 500,000,000
            ],
            [
                'code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,         // KSh 1.00
                'max_charge_amount' => 100000000,   // KSh 1,000,000.00
            ],
            [
                'code' => 'TZS', 'name' => 'Tanzanian Shilling', 'symbol' => 'TSh',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'RWF', 'name' => 'Rwandan Franc', 'symbol' => 'FRw',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 500,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'BIF', 'name' => 'Burundian Franc', 'symbol' => 'FBu',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => false, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],
            [
                'code' => 'ETB', 'name' => 'Ethiopian Birr', 'symbol' => 'Br',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 100000000,
            ],
            [
                'code' => 'SSP', 'name' => 'South Sudanese Pound', 'symbol' => 'SSP',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => false, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],

            // ─────────────── West Africa ───────────────
            [
                'code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,           // ₦1.00
                'max_charge_amount' => 500000000,     // ₦5,000,000.00
            ],
            [
                'code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => '₵',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'XOF', 'name' => 'West African CFA Franc', 'symbol' => 'CFA',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
                'min_charge_amount' => 500,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'XAF', 'name' => 'Central African CFA Franc', 'symbol' => 'FCFA',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
                'min_charge_amount' => 500,
                'max_charge_amount' => 500000000,
            ],

            // ─────────────── Southern Africa ───────────────
            [
                'code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 100000000,
            ],
            [
                'code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'ZK',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 100000000,
            ],
            [
                'code' => 'MWK', 'name' => 'Malawian Kwacha', 'symbol' => 'MK',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],

            // ─────────────── North Africa ───────────────
            [
                'code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'MAD', 'name' => 'Moroccan Dirham', 'symbol' => 'د.م.',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],

            // ─────────────── Middle East ───────────────
            [
                'code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 50000000,
            ],
            [
                'code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 50000000,
            ],

            // ─────────────── Asia ───────────────
            [
                'code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 500000000,
            ],
            [
                'code' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => 'S$',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,
                'max_charge_amount' => 50000000,
            ],
            [
                'code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,           // ¥50
                'max_charge_amount' => 50000000,     // ¥50,000,000
            ],
            [
                'code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],

            // ─────────────── Americas ───────────────
            [
                'code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 50,
                'max_charge_amount' => 50000000,
            ],
            [
                'code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 50000000,
            ],
            [
                'code' => 'MXN', 'name' => 'Mexican Peso', 'symbol' => '$',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => true, 'is_settlement_currency' => true, 'is_presentment_currency' => true,
                'min_charge_amount' => 100,
                'max_charge_amount' => 50000000,
            ],

            // ─────────────── Zero-decimal misc ───────────────
            [
                'code' => 'KRW', 'name' => 'South Korean Won', 'symbol' => '₩',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],
            [
                'code' => 'VND', 'name' => 'Vietnamese Dong', 'symbol' => '₫',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],
            [
                'code' => 'CLP', 'name' => 'Chilean Peso', 'symbol' => '$',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => true,
            ],
            [
                'code' => 'ISK', 'name' => 'Icelandic Króna', 'symbol' => 'kr',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],
            [
                'code' => 'XPF', 'name' => 'CFP Franc', 'symbol' => '₣',
                'exponent' => 0, 'is_zero_decimal' => true,
                'is_active' => true, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],

            // ─────────────── Crypto-adjacent / not enabled by default ───────────────
            [
                'code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => false, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],
            [
                'code' => 'IRR', 'name' => 'Iranian Rial', 'symbol' => '﷼',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => false, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],
            [
                'code' => 'KPW', 'name' => 'North Korean Won', 'symbol' => '₩',
                'exponent' => 2, 'is_zero_decimal' => false,
                'is_active' => false, 'is_settlement_currency' => false, 'is_presentment_currency' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('✓ Seeded ' . count($currencies) . ' currencies.');
    }
}
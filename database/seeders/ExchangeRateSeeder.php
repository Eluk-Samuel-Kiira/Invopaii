<?php

namespace Database\Seeders;

use App\Models\Reference\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Helper to build a row with effective_rate auto-computed the same way as the model
        $mk = function (string $base, string $quote, float $rate, float $markup = 0, ?string $provider = null, $from = null, $to = null) {
            return [
                'base_currency' => $base,
                'quote_currency' => $quote,
                'rate' => $rate,
                'markup_percent' => $markup,
                'effective_rate' => $rate * (1 + $markup / 100),
                'provider' => $provider,
                'effective_from' => $from,
                'effective_to' => $to,
            ];
        };

        $rates = [
            // ─────────────── USD / UGX — historical + current ───────────────
            $mk('USD', 'UGX', 3750.000000000000, 1.5, 'oanda',
                $now->copy()->subDays(90), $now->copy()->subDays(60)),
            $mk('USD', 'UGX', 3820.000000000000, 1.5, 'oanda',
                $now->copy()->subDays(60), $now->copy()->subDays(30)),
            $mk('USD', 'UGX', 3805.500000000000, 1.5, 'oanda',
                $now->copy()->subDays(30), $now->copy()->subDays(7)),
            $mk('USD', 'UGX', 3798.250000000000, 1.5, 'oanda',
                $now->copy()->subDays(7), null),          // ← current

            // ─────────────── USD / KES ───────────────
            $mk('USD', 'KES', 129.500000000000, 1.25, 'oanda',
                $now->copy()->subDays(30), $now->copy()->subDays(7)),
            $mk('USD', 'KES', 128.750000000000, 1.25, 'oanda',
                $now->copy()->subDays(7), null),

            // ─────────────── USD / NGN ───────────────
            $mk('USD', 'NGN', 1550.000000000000, 2.0, 'oanda',
                $now->copy()->subDays(30), $now->copy()->subDays(7)),
            $mk('USD', 'NGN', 1562.400000000000, 2.0, 'oanda',
                $now->copy()->subDays(7), null),

            // ─────────────── USD / GHS ───────────────
            $mk('USD', 'GHS', 15.200000000000, 1.75, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / ZAR ───────────────
            $mk('USD', 'ZAR', 18.450000000000, 1.0, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / TZS ───────────────
            $mk('USD', 'TZS', 2650.000000000000, 1.5, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / RWF ───────────────
            $mk('USD', 'RWF', 1380.000000000000, 1.5, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / ETB ───────────────
            $mk('USD', 'ETB', 122.500000000000, 2.0, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / EGP ───────────────
            $mk('USD', 'EGP', 48.300000000000, 1.25, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── USD / INR ───────────────
            $mk('USD', 'INR', 84.150000000000, 0.75, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── Majors (tighter markup) ───────────────
            $mk('EUR', 'USD', 1.085000000000, 0.5, 'ecb',
                $now->copy()->subDays(30), null),
            $mk('GBP', 'USD', 1.265000000000, 0.5, 'ecb',
                $now->copy()->subDays(30), null),
            $mk('USD', 'JPY', 154.250000000000, 0.5, 'ecb',
                $now->copy()->subDays(30), null),

            // ─────────────── Cross rates (no USD in pair) ───────────────
            $mk('EUR', 'GBP', 0.857500000000, 0.75, 'ecb',
                $now->copy()->subDays(30), null),
            $mk('GBP', 'EUR', 1.165500000000, 0.75, 'ecb',
                $now->copy()->subDays(30), null),

            // ─────────────── Zero-decimal quote currencies ───────────────
            $mk('EUR', 'UGX', 4120.000000000000, 1.75, 'oanda',
                $now->copy()->subDays(30), null),
            $mk('GBP', 'UGX', 4795.000000000000, 1.75, 'oanda',
                $now->copy()->subDays(30), null),

            // ─────────────── Scheduled (future) rate ───────────────
            $mk('USD', 'UGX', 3825.000000000000, 1.5, 'oanda',
                $now->copy()->addDays(7), null),
        ];

        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'base_currency' => $rate['base_currency'],
                    'quote_currency' => $rate['quote_currency'],
                    'effective_from' => $rate['effective_from'],
                ],
                $rate
            );
        }

        $this->command->info('✓ Seeded ' . count($rates) . ' exchange rates.');
    }
}
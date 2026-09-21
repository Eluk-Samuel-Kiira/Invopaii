<?php

namespace App\Services\Payment;

class InvoiceCalculator
{
    /**
     * Given line items and an optional discount, compute all totals.
     * All amounts are in minor units.
     *
     * @param  array  $items      [['quantity'=>10,'unit_amount'=>50000,'tax_percentage'=>18,'name'=>'...'], ...]
     * @param  int    $discountAmount   Fixed discount in minor units (0 if none)
     * @return array
     */
    public static function compute(array $items, int $discountAmount = 0): array
    {
        $subtotal = 0;
        $taxTotal = 0;
        $computedItems = [];

        // Pass 1 — compute line subtotals
        foreach ($items as $item) {
            $line = (int) round((float) $item['quantity'] * (int) $item['unit_amount']);
            $subtotal += $line;
        }

        // Distribute discount proportionally across items (so tax is right)
        $remainingDiscount = min($discountAmount, $subtotal);

        foreach ($items as $index => $item) {
            $qty = (float) $item['quantity'];
            $unit = (int) $item['unit_amount'];
            $lineSubtotal = (int) round($qty * $unit);

            // Proportional discount for this line
            $lineDiscount = 0;
            if ($remainingDiscount > 0 && $subtotal > 0) {
                $lineDiscount = (int) round($lineSubtotal / $subtotal * $remainingDiscount);
            }

            $lineAfterDiscount = $lineSubtotal - $lineDiscount;

            // Tax on the discounted amount
            $taxPct = (float) ($item['tax_percentage'] ?? 0);
            $lineTax = (int) round($lineAfterDiscount * ($taxPct / 100));

            $taxTotal += $lineTax;

            $computedItems[] = [
                'name' => $item['name'] ?? 'Item',
                'description' => $item['description'] ?? null,
                'quantity' => $qty,
                'unit_label' => $item['unit_label'] ?? null,
                'unit_amount' => $unit,
                'currency' => $item['currency'] ?? 'USD',
                'discount_amount' => $lineDiscount,
                'tax_rate_id' => $item['tax_rate_id'] ?? null,
                'tax_percentage' => $taxPct,
                'tax_amount' => $lineTax,
                'subtotal' => $lineSubtotal,
                'total' => $lineAfterDiscount + $lineTax,
                'product_id' => $item['product_id'] ?? null,
                'price_id' => $item['price_id'] ?? null,
                'sort_order' => $index,
            ];
        }

        // Fix rounding: allocate leftover cents to the largest line
        $allocated = array_sum(array_column($computedItems, 'discount_amount'));
        if ($allocated !== $remainingDiscount && $remainingDiscount > 0 && !empty($computedItems)) {
            $diff = $remainingDiscount - $allocated;
            $maxIndex = 0;
            $maxVal = 0;
            foreach ($computedItems as $i => $it) {
                if ($it['subtotal'] > $maxVal) {
                    $maxVal = $it['subtotal'];
                    $maxIndex = $i;
                }
            }
            $computedItems[$maxIndex]['discount_amount'] += $diff;
            $computedItems[$maxIndex]['total'] -= $diff;
        }

        $total = $subtotal - $remainingDiscount + $taxTotal;

        return [
            'items' => $computedItems,
            'subtotal' => $subtotal,
            'discount_total' => $remainingDiscount,
            'tax_total' => $taxTotal,
            'shipping_total' => 0,
            'total' => max($total, 0),
        ];
    }
}
<?php

namespace App\Services\Payment;

use App\Models\Invoice\Invoice;
use App\Models\Payment\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionBillingService
{
    /**
     * Generate the next invoice for a subscription and advance its billing period.
     * Called by the scheduled BillingRunJob for each subscription that's due.
     */
    public static function bill(Subscription $subscription): ?Invoice
    {
        if (!in_array($subscription->status, ['active', 'trialing', 'past_due'])) {
            return null;
        }

        if ($subscription->cancel_at_period_end && $subscription->current_period_end?->isPast()) {
            self::cancelImmediately($subscription);
            return null;
        }

        return DB::transaction(function () use ($subscription) {
            $items = $subscription->items;

            if ($items->isEmpty()) {
                return null;
            }

            // Create the invoice
            $invoice = $subscription->company->invoices()->create([
                'customer_id' => $subscription->customer_id,
                'mode' => $subscription->mode,
                'customer_name' => $subscription->customer?->name,
                'customer_email' => $subscription->customer?->email,
                'customer_phone' => $subscription->customer?->phone,
                'currency' => $subscription->currency,
                'discount_id' => $subscription->discount_id,
                'status' => 'open',
                'collection_method' => $subscription->collection_method,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'subscription_id' => $subscription->id,
                'notes' => $subscription->description,
                'hosted_url' => config('app.url') . '/pay/invoice/' . $subscription->public_id,
            ]);

            // Line items from subscription items
            $subtotal = 0;
            $taxTotal = 0;
            $discountAmount = 0;

            foreach ($items as $item) {
                $lineSubtotal = $item->unit_amount * $item->quantity;
                $subtotal += $lineSubtotal;

                $taxPct = 0.0;
                if ($item->tax_rate_id && $item->taxRate) {
                    $taxPct = (float) $item->taxRate->percentage;
                }

                $lineTax = (int) round($lineSubtotal * $taxPct / 100);
                $taxTotal += $lineTax;

                $invoice->items()->create([
                    'product_id' => $item->product_id,
                    'price_id' => $item->price_id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_amount' => $item->unit_amount,
                    'currency' => $item->currency,
                    'tax_rate_id' => $item->tax_rate_id,
                    'tax_percentage' => $taxPct,
                    'tax_amount' => $lineTax,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal + $lineTax,
                ]);
            }

            // Discount
            if ($subscription->discount && $subscription->discount->is_valid) {
                $discountAmount = $subscription->discount->computeDiscount($subtotal);
            }

            $total = $subtotal - $discountAmount + $taxTotal;

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_total' => $discountAmount,
                'tax_total' => $taxTotal,
                'total' => $total,
                'amount_due' => $total,
            ]);

            // Advance the subscription period
            $periodStart = $subscription->current_period_end ?? now();
            $periodEnd = self::addInterval($periodStart, $subscription->billing_interval, $subscription->billing_interval_count);

            $subscription->update([
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'next_billing_at' => $periodEnd,
                'invoices_generated' => $subscription->invoices_generated + 1,
                'status' => $subscription->status === 'trialing' && $subscription->trial_end?->isPast()
                    ? 'active'
                    : $subscription->status,
            ]);

            return $invoice;
        });
    }

    /**
     * Record a successful payment against a subscription invoice.
     */
    public static function recordPayment(Subscription $subscription, int $amount): void
    {
        $subscription->increment('lifetime_amount', $amount);

        if ($subscription->status === 'past_due') {
            $subscription->update([
                'status' => 'active',
                'failed_payment_attempts' => 0,
                'next_retry_at' => null,
            ]);
        }
    }

    /**
     * Record a failed payment attempt. Marks past_due, schedules retry.
     */
    public static function recordFailure(Subscription $subscription): void
    {
        $attempts = $subscription->failed_payment_attempts + 1;
        $delay = match (true) {
            $attempts <= 1 => now()->addHours(4),
            $attempts === 2 => now()->addDays(1),
            $attempts === 3 => now()->addDays(3),
            default        => now()->addDays(7),
        };

        $subscription->update([
            'status' => 'past_due',
            'failed_payment_attempts' => $attempts,
            'next_retry_at' => $delay,
        ]);

        // After 4 failures, mark unpaid
        if ($attempts >= 4) {
            $subscription->update(['status' => 'unpaid']);
        }
    }

    public static function cancelImmediately(Subscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'ended_at' => now(),
            'cancellation_reason' => $reason,
            'next_billing_at' => null,
        ]);
    }

    public static function scheduleCancellation(Subscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'cancel_at_period_end' => true,
            'cancel_at' => $subscription->current_period_end,
            'cancellation_reason' => $reason,
        ]);
    }

    public static function resume(Subscription $subscription): void
    {
        $subscription->update([
            'cancel_at_period_end' => false,
            'cancel_at' => null,
            'cancellation_reason' => null,
            'status' => $subscription->status === 'paused' ? 'active' : $subscription->status,
            'paused_at' => null,
        ]);
    }

    protected static function addInterval($from, string $interval, int $count)
    {
        $from = \Carbon\Carbon::parse($from);

        return match ($interval) {
            'day'   => $from->copy()->addDays($count),
            'week'  => $from->copy()->addWeeks($count),
            'month' => $from->copy()->addMonths($count),
            'year'  => $from->copy()->addYears($count),
            default => $from->copy()->addMonth(),
        };
    }
}
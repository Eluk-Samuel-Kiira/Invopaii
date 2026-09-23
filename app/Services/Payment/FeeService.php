<?php

namespace App\Services\Payment;

use App\Models\Company\Company;
use App\Models\Payment\AppliedFee;
use App\Models\Payment\FeeSchedule;
use App\Models\Payment\FeeScheduleRule;
use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Model;

class FeeService
{
    /**
     * Compute and record the processing fee for a payment.
     * Returns the AppliedFee or null if no rule matched.
     */
    public static function applyProcessingFee(Payment $payment): ?AppliedFee
    {
        $company = $payment->company;

        if (!$company) return null;

        // Already applied? Return the existing one.
        $existing = AppliedFee::where('feeable_type', Payment::class)
            ->where('feeable_id', $payment->id)
            ->where('fee_type', 'processing')
            ->first();

        if ($existing) return $existing;

        // Resolve schedule: company-specific first, then default
        $schedule = $company->feeSchedule
            ?? FeeSchedule::active()->where('is_default', true)->first()
            ?? FeeSchedule::active()->first();

        if (!$schedule) return null;

        // Find the best matching rule
        $attributes = [
            'fee_type' => 'processing',
            'payment_method' => $payment->payment_method_type,
            'currency' => $payment->currency,
            'country_code' => $payment->customer_country,
            'card_brand' => $payment->card_brand,
            'is_international' => false,
            'amount' => $payment->amount,
        ];

        $rules = $schedule->rules()
            ->active()
            ->forType('processing')
            ->orderBy('priority')
            ->get()
            ->filter(fn ($rule) => $rule->matches($attributes))
            ->sortByDesc(fn ($rule) => $rule->specificity_score)
            ->values();

        if ($rules->isEmpty()) return null;

        $rule = $rules->first();
        $computation = $rule->compute($payment->amount);

        // Record the applied fee
        $appliedFee = AppliedFee::create([
            'company_id' => $payment->company_id,
            'mode' => $payment->mode,
            'feeable_type' => Payment::class,
            'feeable_id' => $payment->id,
            'fee_schedule_rule_id' => $rule->id,
            'fee_type' => 'processing',
            'description' => 'Processing fee',
            'currency' => $payment->currency,
            'base_amount' => $payment->amount,
            'percentage_applied' => $rule->percentage,
            'percentage_component' => $computation['percentage_component'],
            'fixed_component' => $computation['fixed_component'],
            'tax_amount' => $computation['tax_amount'],
            'total_amount' => $computation['total'],
            'is_passed_to_customer' => false,
            'is_waived' => false,
            'calculation_snapshot' => $computation['snapshot'],
        ]);

        // Update the payment
        $payment->update([
            'fee_amount' => $computation['total'] - $computation['tax_amount'],
            'tax_on_fee_amount' => $computation['tax_amount'],
            'net_amount' => $payment->amount - $computation['total'],
        ]);

        return $appliedFee;
    }

    /**
     * Compute a fee for a refund (a refund fee).
     */
    public static function applyRefundFee(Payment $payment, int $refundAmount, Model $refund): ?AppliedFee
    {
        $company = $payment->company;
        if (!$company) return null;

        $schedule = $company->feeSchedule
            ?? FeeSchedule::active()->where('is_default', true)->first();

        if (!$schedule) return null;

        $attributes = [
            'fee_type' => 'refund',
            'currency' => $payment->currency,
            'amount' => $refundAmount,
        ];

        $rules = $schedule->rules()
            ->active()
            ->forType('refund')
            ->orderBy('priority')
            ->get()
            ->filter(fn ($rule) => $rule->matches($attributes))
            ->sortByDesc(fn ($rule) => $rule->specificity_score)
            ->values();

        if ($rules->isEmpty()) return null;

        $rule = $rules->first();
        $computation = $rule->compute($refundAmount);

        return AppliedFee::create([
            'company_id' => $payment->company_id,
            'mode' => $payment->mode,
            'feeable_type' => get_class($refund),
            'feeable_id' => $refund->id,
            'fee_schedule_rule_id' => $rule->id,
            'fee_type' => 'refund',
            'description' => 'Refund fee',
            'currency' => $payment->currency,
            'base_amount' => $refundAmount,
            'percentage_applied' => $rule->percentage,
            'percentage_component' => $computation['percentage_component'],
            'fixed_component' => $computation['fixed_component'],
            'tax_amount' => $computation['tax_amount'],
            'total_amount' => $computation['total'],
            'is_passed_to_customer' => false,
            'is_waived' => false,
            'calculation_snapshot' => $computation['snapshot'],
        ]);
    }
}
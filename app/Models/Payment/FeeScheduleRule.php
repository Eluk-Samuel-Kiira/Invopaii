<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeScheduleRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_schedule_id', 'fee_type',
        'payment_method', 'currency', 'country_code', 'card_brand', 'is_international',
        'percentage', 'fixed_amount', 'fixed_amount_currency',
        'minimum_fee', 'maximum_fee',
        'min_transaction_amount', 'max_transaction_amount',
        'tax_percentage', 'priority', 'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:4',
        'fixed_amount' => 'integer',
        'minimum_fee' => 'integer',
        'maximum_fee' => 'integer',
        'min_transaction_amount' => 'integer',
        'max_transaction_amount' => 'integer',
        'tax_percentage' => 'decimal:3',
        'is_international' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /* ---------- Relations ---------- */

    public function schedule()
    {
        return $this->belongsTo(FeeSchedule::class, 'fee_schedule_id');
    }

    public function appliedFees()
    {
        return $this->hasMany(AppliedFee::class, 'fee_schedule_rule_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function scopeForType($query, string $feeType)
    {
        return $query->where('fee_type', $feeType);
    }

    /* ---------- Matching ---------- */

    /**
     * Does this rule apply to the given attributes?
     *
     * $attributes = [
     *   'fee_type' => 'processing',
     *   'payment_method' => 'mobile_money',
     *   'currency' => 'UGX',
     *   'country_code' => 'UG',
     *   'card_brand' => null,
     *   'is_international' => false,
     *   'amount' => 75000,
     * ]
     */
    public function matches(array $attributes): bool
    {
        if ($this->fee_type !== ($attributes['fee_type'] ?? null)) return false;

        if ($this->payment_method && $this->payment_method !== ($attributes['payment_method'] ?? null)) return false;
        if ($this->currency && $this->currency !== ($attributes['currency'] ?? null)) return false;
        if ($this->country_code && $this->country_code !== ($attributes['country_code'] ?? null)) return false;
        if ($this->card_brand && $this->card_brand !== ($attributes['card_brand'] ?? null)) return false;
        if ($this->is_international !== null && $this->is_international !== ($attributes['is_international'] ?? false)) return false;

        $amount = $attributes['amount'] ?? null;
        if ($this->min_transaction_amount !== null && $amount !== null && $amount < $this->min_transaction_amount) return false;
        if ($this->max_transaction_amount !== null && $amount !== null && $amount > $this->max_transaction_amount) return false;

        return true;
    }

    /**
     * How specific is this rule? Higher = more specific = higher priority when multiple match.
     */
    public function getSpecificityScoreAttribute(): int
    {
        $score = 0;
        if ($this->payment_method) $score += 16;
        if ($this->currency) $score += 8;
        if ($this->country_code) $score += 8;
        if ($this->card_brand) $score += 4;
        if ($this->is_international !== null) $score += 2;
        if ($this->min_transaction_amount !== null || $this->max_transaction_amount !== null) $score += 1;
        return $score;
    }

    /* ---------- Fee computation ---------- */

    /**
     * Compute the fee breakdown for a given base amount.
     *
     * @return array{percentage_component:int, fixed_component:int, pre_tax:int, tax_amount:int, total:int, snapshot:array}
     */
    public function compute(int $baseAmount): array
    {
        $percentageComponent = (int) round($baseAmount * ((float) $this->percentage / 100));
        $fixedComponent = (int) $this->fixed_amount;

        $preTax = $percentageComponent + $fixedComponent;

        // Apply min/max clamping
        $clamped = $preTax;
        if ($this->minimum_fee !== null && $clamped < $this->minimum_fee) {
            $clamped = (int) $this->minimum_fee;
        }
        if ($this->maximum_fee !== null && $clamped > $this->maximum_fee) {
            $clamped = (int) $this->maximum_fee;
        }

        $taxAmount = (int) round($clamped * ((float) $this->tax_percentage / 100));
        $total = $clamped + $taxAmount;

        return [
            'percentage_component' => $percentageComponent,
            'fixed_component' => $fixedComponent,
            'pre_tax' => $preTax,
            'clamped' => $clamped,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'snapshot' => [
                'base_amount' => $baseAmount,
                'percentage' => (float) $this->percentage,
                'fixed_amount' => $fixedComponent,
                'minimum_fee' => $this->minimum_fee,
                'maximum_fee' => $this->maximum_fee,
                'tax_percentage' => (float) $this->tax_percentage,
                'pre_tax_fee' => $preTax,
                'after_clamp' => $clamped,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'rule_id' => $this->id,
                'schedule_id' => $this->fee_schedule_id,
            ],
        ];
    }

    /* ---------- Accessors ---------- */

    public function getSummaryAttribute(): string
    {
        $parts = [];

        if ((float) $this->percentage > 0) {
            $parts[] = rtrim(rtrim(number_format((float) $this->percentage, 4), '0'), '.') . '%';
        }
        if ($this->fixed_amount > 0) {
            $curr = $this->fixed_amount_currency ?? $this->currency ?? '';
            $parts[] = ($curr ? $curr . ' ' : '') . number_format($this->fixed_amount);
        }

        $summary = implode(' + ', $parts) ?: '0';

        if ($this->minimum_fee !== null) $summary .= ' (min ' . number_format($this->minimum_fee) . ')';
        if ($this->maximum_fee !== null) $summary .= ' (max ' . number_format($this->maximum_fee) . ')';
        if ((float) $this->tax_percentage > 0) $summary .= ' + ' . $this->tax_percentage . '% tax';

        return $summary;
    }

    public function getScopeLabelAttribute(): string
    {
        $parts = [];

        if ($this->payment_method) $parts[] = str_replace('_', ' ', $this->payment_method);
        if ($this->currency) $parts[] = $this->currency;
        if ($this->country_code) $parts[] = $this->country_code;
        if ($this->card_brand) $parts[] = $this->card_brand;
        if ($this->is_international === true) $parts[] = 'international';
        if ($this->is_international === false) $parts[] = 'domestic';

        return $parts ? implode(' · ', $parts) : 'Any';
    }
}
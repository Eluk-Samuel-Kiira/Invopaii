<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppliedFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'company_id', 'mode',
        'feeable_type', 'feeable_id',
        'fee_schedule_rule_id',
        'fee_type', 'description',
        'currency', 'base_amount',
        'percentage_applied', 'percentage_component', 'fixed_component',
        'tax_amount', 'total_amount',
        'is_passed_to_customer', 'is_waived', 'waiver_reason',
        'calculation_snapshot',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'percentage_applied' => 'decimal:4',
        'percentage_component' => 'integer',
        'fixed_component' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'is_passed_to_customer' => 'boolean',
        'is_waived' => 'boolean',
        'calculation_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($f) => $f->uuid ??= (string) Str::uuid());
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function rule() { return $this->belongsTo(FeeScheduleRule::class, 'fee_schedule_rule_id'); }
    public function feeable() { return $this->morphTo(); }

    /* ---------- Scopes ---------- */

    public function scopeForType($query, string $feeType)
    {
        return $query->where('fee_type', $feeType);
    }

    public function scopeNotWaived($query)
    {
        return $query->where('is_waived', false);
    }

    public function scopeNotPassedToCustomer($query)
    {
        return $query->where('is_passed_to_customer', false);
    }

    /* ---------- Accessors ---------- */

    public function getFeeTypeLabelAttribute(): string
    {
        return match ($this->fee_type) {
            'processing'          => 'Processing',
            'refund'              => 'Refund',
            'chargeback'          => 'Chargeback',
            'payout'              => 'Payout',
            'fx'                  => 'FX',
            'international_card'  => 'International Card',
            'monthly'             => 'Monthly',
            default               => ucfirst(str_replace('_', ' ', $this->fee_type)),
        };
    }

    public function getIsReversedAttribute(): bool
    {
        return !empty($this->calculation_snapshot['reversed_at']);
    }
}
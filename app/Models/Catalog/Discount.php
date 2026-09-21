<?php

namespace App\Models\Catalog;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'code', 'name', 'type',
        'percent_off', 'amount_off', 'currency',
        'duration', 'duration_in_months',
        'max_redemptions', 'times_redeemed', 'max_redemptions_per_customer',
        'minimum_order_amount',
        'starts_at', 'expires_at', 'is_active',
        'applies_to_product_ids', 'metadata',
    ];

    protected $casts = [
        'percent_off' => 'decimal:3',
        'amount_off' => 'integer',
        'duration_in_months' => 'integer',
        'max_redemptions' => 'integer',
        'times_redeemed' => 'integer',
        'max_redemptions_per_customer' => 'integer',
        'minimum_order_amount' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'applies_to_product_ids' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Discount $d) {
            $d->uuid ??= (string) Str::uuid();
            $d->public_id ??= 'disc_' . Str::lower(Str::random(24));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function redemptions()
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_redemptions && $this->times_redeemed >= $this->max_redemptions) return false;
        return true;
    }

    public function getLabelAttribute(): string
    {
        if ($this->type === 'percentage') {
            return "{$this->percent_off}% off";
        }
        return number_format($this->amount_off / 100, 2) . " {$this->currency} off";
    }

    /**
     * Compute the discount for a given subtotal (minor units).
     */
    public function computeDiscount(int $subtotal): int
    {
        if ($this->minimum_order_amount && $subtotal < $this->minimum_order_amount) {
            return 0;
        }

        if ($this->type === 'percentage') {
            return (int) round($subtotal * ((float) $this->percent_off / 100));
        }

        return min($this->amount_off, $subtotal);
    }
}
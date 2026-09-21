<?php

namespace App\Models\Catalog;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Price extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'product_id', 'mode',
        'nickname', 'currency', 'unit_amount', 'billing_scheme', 'tiers',
        'type', 'recurring_interval', 'recurring_interval_count', 'trial_period_days',
        'is_active', 'tax_inclusive', 'metadata',
    ];

    protected $casts = [
        'tiers' => 'array',
        'metadata' => 'array',
        'unit_amount' => 'integer',
        'recurring_interval_count' => 'integer',
        'trial_period_days' => 'integer',
        'is_active' => 'boolean',
        'tax_inclusive' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Price $p) {
            $p->uuid ??= (string) Str::uuid();
            $p->public_id ??= 'price_' . Str::lower(Str::random(24));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getIntervalLabelAttribute(): string
    {
        if ($this->type !== 'recurring') return 'One-time';
        $count = $this->recurring_interval_count ?? 1;
        $interval = $this->recurring_interval ?? 'month';
        return $count > 1 ? "Every {$count} {$interval}s" : "Every {$interval}";
    }
}
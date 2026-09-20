<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'markup_percent',
        'effective_rate',
        'provider',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'rate' => 'decimal:12',
        'markup_percent' => 'decimal:4',
        'effective_rate' => 'decimal:12',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    /* ---------- Boot ---------- */

    protected static function booted(): void
    {
        // Auto-compute effective_rate from rate + markup before saving
        static::saving(function (ExchangeRate $rate) {
            $rate->base_currency = strtoupper($rate->base_currency);
            $rate->quote_currency = strtoupper($rate->quote_currency);

            $markup = (float) ($rate->markup_percent ?? 0);
            $rate->effective_rate = (float) $rate->rate * (1 + $markup / 100);
        });
    }

    /* ---------- Scopes ---------- */

    public function scopePair(Builder $query, string $base, string $quote): Builder
    {
        return $query->where('base_currency', strtoupper($base))
                     ->where('quote_currency', strtoupper($quote));
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('effective_from', '<=', $now)
                     ->where(function ($q) use ($now) {
                         $q->whereNull('effective_to')
                           ->orWhere('effective_to', '>', $now);
                     });
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()->latest('effective_from');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('base_currency', 'like', "%{$term}%")
              ->orWhere('quote_currency', 'like', "%{$term}%")
              ->orWhere('provider', 'like', "%{$term}%")
              ->orWhereRaw("CONCAT(base_currency, '/', quote_currency) LIKE ?", ["%{$term}%"]);
        });
    }

    /* ---------- Accessors ---------- */

    public function getPairAttribute(): string
    {
        return $this->base_currency . '/' . $this->quote_currency;
    }

    public function getIsActiveAttribute(): bool
    {
        $now = now();

        if ($this->effective_from > $now) return false;
        if ($this->effective_to && $this->effective_to <= $now) return false;

        return true;
    }

    public function getStatusAttribute(): string
    {
        $now = now();

        if ($this->effective_from > $now) return 'scheduled';
        if ($this->effective_to && $this->effective_to <= $now) return 'expired';
        return 'active';
    }

    /**
     * Convert a major-unit amount using the effective rate.
     * e.g. convert(100) on USD/UGX @ 3800 → 380000
     */
    public function convert(float $amount): float
    {
        return $amount * (float) $this->effective_rate;
    }

    /**
     * Same, but using the raw (unmarked) rate.
     */
    public function convertRaw(float $amount): float
    {
        return $amount * (float) $this->rate;
    }
}
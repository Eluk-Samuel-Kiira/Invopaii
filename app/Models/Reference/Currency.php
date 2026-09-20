<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'exponent',
        'is_zero_decimal',
        'is_active',
        'is_settlement_currency',
        'is_presentment_currency',
        'min_charge_amount',
        'max_charge_amount',
    ];

    protected $casts = [
        'exponent' => 'integer',
        'is_zero_decimal' => 'boolean',
        'is_active' => 'boolean',
        'is_settlement_currency' => 'boolean',
        'is_presentment_currency' => 'boolean',
        'min_charge_amount' => 'integer',
        'max_charge_amount' => 'integer',
    ];

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSettlement($query)
    {
        return $query->where('is_settlement_currency', true);
    }

    public function scopePresentment($query)
    {
        return $query->where('is_presentment_currency', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('symbol', 'like', "%{$term}%");
        });
    }

    /* ---------- Accessors ---------- */

    /**
     * Number of decimal places for display: 2 for normal, 0 for zero-decimal.
     */
    public function getDecimalPlacesAttribute(): int
    {
        return $this->is_zero_decimal ? 0 : (int) $this->exponent;
    }

    /**
     * Human-readable minor-unit label, e.g. "cents" / "units".
     */
    public function getMinorUnitLabelAttribute(): string
    {
        return $this->is_zero_decimal ? 'units' : 'cents';
    }

    /**
     * Format a minor-unit integer as a human string.
     * e.g. formatMinor(12345) on USD → "123.45", on UGX → "12345".
     */
    public function formatMinor(?int $amount): ?string
    {
        if ($amount === null) return null;

        if ($this->is_zero_decimal) {
            return number_format($amount, 0);
        }

        return number_format($amount / (10 ** $this->exponent), $this->exponent);
    }

    /**
     * Convert a major-unit decimal into minor units.
     * e.g. toMinor(123.45) on USD → 12345, on UGX → 123.
     */
    public function toMinor(float $major): int
    {
        return (int) round($major * (10 ** $this->decimal_places));
    }
}
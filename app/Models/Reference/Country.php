<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'iso2',
        'iso3',
        'name',
        'official_name',
        'phone_code',
        'default_currency',
        'region',
        'subregion',
        'flag_emoji',
        'is_supported',
        'collections_enabled',
        'payouts_enabled',
        'is_high_risk',
        'is_sanctioned',
        'required_business_documents',
        'supported_payment_methods',
        'metadata',
    ];

    protected $casts = [
        'is_supported' => 'boolean',
        'collections_enabled' => 'boolean',
        'payouts_enabled' => 'boolean',
        'is_high_risk' => 'boolean',
        'is_sanctioned' => 'boolean',
        'required_business_documents' => 'array',
        'supported_payment_methods' => 'array',
        'metadata' => 'array',
    ];

    /* ---------- Scopes ---------- */

    public function scopeSupported($query)
    {
        return $query->where('is_supported', true);
    }

    public function scopeCollectionsEnabled($query)
    {
        return $query->where('collections_enabled', true);
    }

    public function scopePayoutsEnabled($query)
    {
        return $query->where('payouts_enabled', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('official_name', 'like', "%{$term}%")
              ->orWhere('iso2', 'like', "%{$term}%")
              ->orWhere('iso3', 'like', "%{$term}%")
              ->orWhere('phone_code', 'like', "%{$term}%")
              ->orWhere('default_currency', 'like', "%{$term}%");
        });
    }

    /* ---------- Accessors ---------- */

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->flag_emoji ? $this->flag_emoji . ' ' : '') . $this->name);
    }

    public function getMarketStatusAttribute(): string
    {
        if ($this->is_sanctioned) return 'sanctioned';
        if (!$this->is_supported) return 'unsupported';
        if ($this->is_high_risk) return 'high_risk';
        return 'active';
    }

    public function getSupportedMethodsAttribute(): array
    {
        return $this->supported_payment_methods ?? [];
    }
}
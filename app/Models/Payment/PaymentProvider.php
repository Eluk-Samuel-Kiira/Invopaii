<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'logo_path', 'type',
        'supported_countries', 'supported_currencies', 'supported_methods', 'capabilities',
        'supports_test_mode', 'is_active', 'priority',
        'success_rate', 'avg_latency_ms', 'health_status', 'health_checked_at',
        'webhook_path', 'settings',
    ];

    protected $casts = [
        'supported_countries' => 'array',
        'supported_currencies' => 'array',
        'supported_methods' => 'array',
        'capabilities' => 'array',
        'settings' => 'array',
        'supports_test_mode' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'success_rate' => 'decimal:2',
        'avg_latency_ms' => 'integer',
        'health_checked_at' => 'datetime',
    ];

    /* ---------- Relations ---------- */

    public function credentials()
    {
        return $this->hasMany(ProviderCredential::class);
    }

    public function routingRules()
    {
        return $this->hasMany(RoutingRule::class);
    }

    public function fallbackRules()
    {
        return $this->hasMany(RoutingRule::class, 'fallback_provider_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('type', 'like', "%{$term}%");
        });
    }

    /* ---------- Accessors ---------- */

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'mobile_money' => 'Mobile Money',
            'acquirer' => 'Acquirer',
            'aggregator' => 'Aggregator',
            'wallet' => 'Wallet',
            'bank' => 'Bank',
            default => ucfirst($this->type),
        };
    }

    public function getHealthBadgeAttribute(): array
    {
        return match ($this->health_status) {
            'healthy'  => ['label' => 'Healthy',  'tone' => 'success'],
            'degraded' => ['label' => 'Degraded', 'tone' => 'warning'],
            'down'     => ['label' => 'Down',     'tone' => 'danger'],
            default    => ['label' => ucfirst($this->health_status), 'tone' => 'secondary'],
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return $this->is_active
            ? ['label' => 'Active', 'tone' => 'success']
            : ['label' => 'Inactive', 'tone' => 'secondary'];
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path ? asset($this->logo_path) : asset('assets/media/svg/brand-logos/blank.svg');
    }

    /* ---------- Capability checks ---------- */

    public function supportsMethod(string $method): bool
    {
        $methods = $this->supported_methods ?? [];
        return empty($methods) || in_array($method, $methods, true);
    }

    public function supportsCountry(string $iso2): bool
    {
        $countries = $this->supported_countries ?? [];
        return empty($countries) || in_array($iso2, $countries, true);
    }

    public function supportsCurrency(string $currency): bool
    {
        $currencies = $this->supported_currencies ?? [];
        return empty($currencies) || in_array($currency, $currencies, true);
    }

    public function hasCapability(string $cap): bool
    {
        $caps = $this->capabilities ?? [];
        return !empty($caps[$cap]);
    }

    /* ---------- Credentials ---------- */

    public function getCredentialFor(?int $companyId, string $mode): ?ProviderCredential
    {
        return $this->credentials()
            ->where('company_id', $companyId)
            ->where('mode', $mode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve a usable credential for a given scope.
     * Company-specific first, then platform-wide fallback.
     */
    public function getUsableCredentialFor(?int $companyId, string $mode): ?ProviderCredential
    {
        return $this->getCredentialFor($companyId, $mode)
            ?? $this->getCredentialFor(null, $mode);
    }

    public function settlements()
    {
        return $this->hasMany(\App\Models\Payment\ProviderSettlement::class, 'payment_provider_id');
    }   

}
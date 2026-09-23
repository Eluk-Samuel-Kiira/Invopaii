<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'payment_provider_id', 'fallback_provider_id', 'mode',
        'name', 'payment_method', 'currency', 'country_code', 'card_brand',
        'min_amount', 'max_amount', 'conditions',
        'priority', 'traffic_percentage', 'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'priority' => 'integer',
        'traffic_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    /* ---------- Relations ---------- */

    public function provider()
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function fallbackProvider()
    {
        return $this->belongsTo(PaymentProvider::class, 'fallback_provider_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }

    /**
     * Match rules against a payment's attributes, ordered by priority.
     * Highest priority (lowest number) wins. Company-specific beats global.
     */
    public static function matchFor(array $attributes)
    {
        $method = $attributes['payment_method'] ?? null;
        $country = $attributes['country_code'] ?? null;
        $currency = $attributes['currency'] ?? null;
        $brand = $attributes['card_brand'] ?? null;
        $amount = $attributes['amount'] ?? null;
        $companyId = $attributes['company_id'] ?? null;
        $mode = $attributes['mode'] ?? null;

        return static::query()
            ->active()
            ->when($companyId, fn ($q) => $q->forCompany($companyId), fn ($q) => $q->global())
            ->when($mode, fn ($q) => $q->where(function ($qq) use ($mode) {
                $qq->whereNull('mode')->orWhere('mode', $mode);
            }))
            ->with(['provider', 'fallbackProvider'])
            ->get()
            ->filter(function ($rule) use ($method, $country, $currency, $brand, $amount) {
                if ($rule->payment_method && $rule->payment_method !== $method) return false;
                if ($rule->country_code && $rule->country_code !== $country) return false;
                if ($rule->currency && $rule->currency !== $currency) return false;
                if ($rule->card_brand && $rule->card_brand !== $brand) return false;
                if ($rule->min_amount !== null && $amount !== null && $amount < $rule->min_amount) return false;
                if ($rule->max_amount !== null && $amount !== null && $amount > $rule->max_amount) return false;
                return true;
            })
            ->sortBy([
                fn ($a, $b) => $a->company_id === null ? 1 : -1, // company-specific first
                ['priority', 'asc'],
            ])
            ->values();
    }
}
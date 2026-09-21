<?php

namespace App\Models\Customer;

use App\Models\Company\Company;
use App\Models\Payment\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'name', 'email', 'phone', 'reference', 'description',
        'country_code', 'preferred_currency', 'preferred_locale', 'timezone',
        'default_payment_method_id', 'default_address_id',
        'lifetime_value', 'successful_payments_count', 'disputed_payments_count',
        'first_paid_at', 'last_paid_at',
        'is_blocked', 'blocked_reason', 'tax_exempt', 'tax_id',
        'shipping_address', 'metadata',
    ];

    protected $casts = [
        'lifetime_value' => 'integer',
        'successful_payments_count' => 'integer',
        'disputed_payments_count' => 'integer',
        'first_paid_at' => 'datetime',
        'last_paid_at' => 'datetime',
        'is_blocked' => 'boolean',
        'tax_exempt' => 'boolean',
        'shipping_address' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $c) {
            $c->uuid ??= (string) Str::uuid();
            $c->public_id ??= 'cus_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function defaultPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

    public function defaultAddress()
    {
        return $this->belongsTo(CustomerAddress::class, 'default_address_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('reference', 'like', "%{$term}%")
              ->orWhere('public_id', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    /* ---------- Accessors ---------- */

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: ($this->email ?: $this->public_id);
    }

    public function getInitialsAttribute(): string
    {
        $name = $this->name ?: $this->email ?: '?';
        $parts = preg_split('/[\s@]+/', $name);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $initials .= strtoupper(substr($p, 0, 1));
        }
        return $initials ?: '?';
    }

    public function getStatusBadgeAttribute(): array
    {
        if ($this->is_blocked) return ['label' => 'Blocked', 'tone' => 'danger'];
        return ['label' => 'Active', 'tone' => 'success'];
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice\Invoice::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Payment\Subscription::class);
    }


}
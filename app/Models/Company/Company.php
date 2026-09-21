<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Reference\Country;


class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id',
        'name', 'legal_name', 'slug', 'email', 'support_email', 'support_phone',
        'website', 'logo_path', 'brand_color',
        'country_id', 'business_type', 'industry', 'mcc',
        'registration_number', 'tax_identification_number', 'incorporated_on',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code',
        'default_currency', 'settlement_currency', 'timezone', 'statement_descriptor',
        'status', 'kyb_status', 'live_mode_enabled',
        'charges_enabled', 'payouts_enabled',
        'onboarding_step', 'requirements_due',
        'activated_at', 'suspended_at', 'suspension_reason',
        'fee_schedule_id', 'risk_level', 'risk_score',
        'payout_schedule', 'payout_delay_days',
        'reserve_percent', 'reserve_hold_days',
        'owner_id', 'settings', 'metadata',
    ];

    protected $casts = [
        'incorporated_on' => 'date',
        'live_mode_enabled' => 'boolean',
        'charges_enabled' => 'boolean',
        'payouts_enabled' => 'boolean',
        'requirements_due' => 'array',
        'settings' => 'array',
        'metadata' => 'array',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'reserve_percent' => 'decimal:2',
        'risk_score' => 'integer',
    ];

    protected $hidden = ['settings', 'metadata'];

    /* ---------- Boot ---------- */

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->uuid ??= (string) Str::uuid();
            $company->public_id ??= 'acct_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot(['role', 'permissions', 'can_access_live_mode', 'status', 'last_accessed_at'])
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function feeSchedule()
    {
        return $this->belongsTo(FeeSchedule::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('legal_name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('public_id', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePendingKyb($query)
    {
        return $query->whereIn('kyb_status', ['unverified', 'pending']);
    }

    /* ---------- Accessors ---------- */

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->legal_name ?: $this->slug;
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === 'active' && $this->live_mode_enabled;
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active'      => ['label' => 'Active',      'tone' => 'success'],
            'pending'     => ['label' => 'Pending',     'tone' => 'warning'],
            'in_review'   => ['label' => 'In Review',   'tone' => 'info'],
            'restricted'  => ['label' => 'Restricted',  'tone' => 'warning'],
            'suspended'   => ['label' => 'Suspended',   'tone' => 'danger'],
            'rejected'    => ['label' => 'Rejected',    'tone' => 'danger'],
            'closed'      => ['label' => 'Closed',      'tone' => 'secondary'],
            default       => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getKybBadgeAttribute(): array
    {
        return match ($this->kyb_status) {
            'verified'   => ['label' => 'Verified',   'tone' => 'success'],
            'pending'    => ['label' => 'Pending',    'tone' => 'info'],
            'rejected'   => ['label' => 'Rejected',   'tone' => 'danger'],
            'unverified' => ['label' => 'Unverified', 'tone' => 'warning'],
            default      => ['label' => ucfirst($this->kyb_status), 'tone' => 'secondary'],
        };
    }

    /* ---------- Business helpers ---------- */

    public function canTransact(): bool
    {
        return $this->charges_enabled
            && $this->status === 'active'
            && $this->kyb_status === 'verified';
    }

    public function canPayout(): bool
    {
        return $this->canTransact()
            && $this->payouts_enabled
            && $this->settlement_currency;
    }


    public function representatives()
    {
        return $this->hasMany(CompanyRepresentative::class);
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function verificationChecks()
    {
        return $this->morphMany(VerificationCheck::class, 'checkable');
    }

    public function bankAccounts()
    {
        return $this->hasMany(CompanyBankAccount::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function apiRequestLogs()
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    public function events()
    {
        return $this->hasMany(\App\Models\Webhook\Event::class);
    }

    public function webhookEndpoints()
    {
        return $this->hasMany(\App\Models\Webhook\WebhookEndpoint::class);
    }

    public function webhookDeliveries()
    {
        return $this->hasMany(\App\Models\Webhook\WebhookDelivery::class);
    }

    public function customers()
    {
        return $this->hasMany(\App\Models\Customer\Customer::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(\App\Models\Payment\PaymentMethod::class);
    }

    public function products()
    {
        return $this->hasMany(\App\Models\Catalog\Product::class);
    }

    public function prices()
    {
        return $this->hasMany(\App\Models\Catalog\Price::class);
    }

    public function taxRates()
    {
        return $this->hasMany(\App\Models\Catalog\TaxRate::class);
    }

    public function discounts()
    {
        return $this->hasMany(\App\Models\Catalog\Discount::class);
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Payment\Invoice::class);
    }

    public function invoiceSequences()
    {
        return $this->hasMany(\App\Models\Payment\InvoiceSequence::class);
    }

    public function paymentLinks()
    {
        return $this->hasMany(\App\Models\Payment\PaymentLink::class);
    }

    public function checkoutSessions()
    {
        return $this->hasMany(\App\Models\Payment\CheckoutSession::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Payment\Subscription::class);
    }
}
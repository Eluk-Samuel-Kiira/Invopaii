<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'customer_id', 'mode',
        'type',
        'card_brand', 'card_last_four', 'card_bin',
        'card_exp_month', 'card_exp_year', 'card_funding', 'card_country', 'card_issuer',
        'three_d_secure_support',
        'bank_name', 'bank_code', 'account_last_four',
        'mobile_network', 'msisdn_last_four',
        'provider', 'provider_token', 'fingerprint',
        'is_reusable', 'is_default',
        'status', 'last_used_at', 'billing_details', 'metadata',
    ];

    protected $casts = [
        'provider_token' => 'encrypted',
        'billing_details' => 'array',
        'metadata' => 'array',
        'is_reusable' => 'boolean',
        'is_default' => 'boolean',
        'last_used_at' => 'datetime',
        'card_exp_month' => 'integer',
        'card_exp_year' => 'integer',
    ];

    protected $hidden = ['provider_token', 'fingerprint'];

    protected static function booted(): void
    {
        static::creating(function (PaymentMethod $pm) {
            $pm->uuid ??= (string) Str::uuid();
            $pm->public_id ??= 'pm_' . Str::lower(Str::random(24));
        });

        static::saving(function (PaymentMethod $pm) {
            if ($pm->is_default && $pm->isDirty('is_default') && $pm->customer_id) {
                static::where('customer_id', $pm->customer_id)
                    ->where('id', '!=', $pm->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active'  => ['label' => 'Active',  'tone' => 'success'],
            'expired' => ['label' => 'Expired', 'tone' => 'warning'],
            'revoked' => ['label' => 'Revoked', 'tone' => 'danger'],
            'failed'  => ['label' => 'Failed',  'tone' => 'danger'],
            default   => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->type === 'card') {
            $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Card';
            return "{$brand} •••• {$this->card_last_four}";
        }
        if ($this->type === 'mobile_money') {
            return ucfirst($this->mobile_network ?? 'Mobile Money') . ' •••• ' . $this->msisdn_last_four;
        }
        if ($this->type === 'bank_account') {
            return ($this->bank_name ?? 'Bank') . ' •••• ' . $this->account_last_four;
        }
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->type !== 'card' || !$this->card_exp_year) return false;
        $expires = \Carbon\Carbon::create($this->card_exp_year, $this->card_exp_month, 1)->endOfMonth();
        return $expires->isPast();
    }
}
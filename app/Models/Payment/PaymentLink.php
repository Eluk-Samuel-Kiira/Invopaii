<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Invoice\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'created_by_id', 'mode',
        'slug', 'title', 'description', 'image_path',
        'amount_type', 'currency', 'amount', 'minimum_amount', 'maximum_amount',
        'suggested_amounts', 'allow_quantity_adjustment',
        'collect_customer_name', 'collect_email', 'collect_phone',
        'collect_billing_address', 'collect_shipping_address',
        'custom_fields', 'allowed_payment_methods', 'allowed_countries',
        'usage_type', 'max_payments', 'payments_count', 'amount_collected',
        'active_from', 'expires_at',
        'after_completion', 'success_url', 'cancel_url', 'success_message', 'send_receipt',
        'pass_fees_to_customer', 'reference_prefix', 'statement_descriptor',
        'status', 'requires_password', 'password_hash', 'metadata',
    ];

    protected $casts = [
        'suggested_amounts' => 'array',
        'custom_fields' => 'array',
        'allowed_payment_methods' => 'array',
        'allowed_countries' => 'array',
        'metadata' => 'array',
        'amount' => 'integer',
        'minimum_amount' => 'integer',
        'maximum_amount' => 'integer',
        'payments_count' => 'integer',
        'amount_collected' => 'integer',
        'max_payments' => 'integer',
        'allow_quantity_adjustment' => 'boolean',
        'collect_customer_name' => 'boolean',
        'collect_email' => 'boolean',
        'collect_phone' => 'boolean',
        'collect_billing_address' => 'boolean',
        'collect_shipping_address' => 'boolean',
        'send_receipt' => 'boolean',
        'pass_fees_to_customer' => 'boolean',
        'requires_password' => 'boolean',
        'active_from' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    protected static function booted(): void
    {
        static::creating(function (PaymentLink $link) {
            $link->uuid ??= (string) Str::uuid();
            $link->public_id ??= 'plink_' . Str::lower(Str::random(24));

            if (empty($link->slug)) {
                $link->slug = $link->generateUniqueSlug($link->title ?? 'link');
            }
        });
    }

    public function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'link';
        $slug = $base;
        $i = 2;

        while (static::where('mode', $this->mode ?? 'test')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by_id'); }
    public function items() { return $this->hasMany(PaymentLinkItem::class); }
    public function checkoutSessions() { return $this->hasMany(CheckoutSession::class); }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('public_id', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active'    => ['label' => 'Active',    'tone' => 'success'],
            'inactive'  => ['label' => 'Inactive',  'tone' => 'secondary'],
            'expired'   => ['label' => 'Expired',   'tone' => 'warning'],
            'completed' => ['label' => 'Completed', 'tone' => 'info'],
            'archived'  => ['label' => 'Archived',  'tone' => 'secondary'],
            default     => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getAmountTypeLabelAttribute(): string
    {
        return match ($this->amount_type) {
            'fixed'             => 'Fixed amount',
            'customer_chooses'  => 'Customer chooses',
            'line_items'        => 'Line items',
            default             => ucfirst($this->amount_type),
        };
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/pay/' . $this->public_id);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
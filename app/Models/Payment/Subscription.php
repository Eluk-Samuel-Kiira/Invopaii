<?php

namespace App\Models\Payment;

use App\Models\Catalog\Discount;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'customer_id', 'payment_method_id', 'mode',
        'description', 'currency', 'status', 'collection_method',
        'billing_interval', 'billing_interval_count', 'billing_cycle_anchor_day',
        'current_period_start', 'current_period_end',
        'trial_start', 'trial_end',
        'started_at', 'next_billing_at',
        'paused_at', 'cancel_at', 'cancelled_at', 'ended_at',
        'cancel_at_period_end', 'cancellation_reason',
        'discount_id', 'failed_payment_attempts', 'next_retry_at',
        'invoices_generated', 'lifetime_amount', 'metadata',
    ];

    protected $casts = [
        'billing_interval_count' => 'integer',
        'billing_cycle_anchor_day' => 'integer',
        'cancel_at_period_end' => 'boolean',
        'failed_payment_attempts' => 'integer',
        'invoices_generated' => 'integer',
        'lifetime_amount' => 'integer',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'started_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'paused_at' => 'datetime',
        'cancel_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'ended_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Subscription $s) {
            $s->uuid ??= (string) Str::uuid();
            $s->public_id ??= 'sub_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function discount() { return $this->belongsTo(Discount::class); }
    public function items() { return $this->hasMany(SubscriptionItem::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('public_id', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeDueForBilling($query)
    {
        return $query->whereIn('status', ['active', 'trialing', 'past_due'])
            ->where('next_billing_at', '<=', now());
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'incomplete'  => ['label' => 'Incomplete',  'tone' => 'warning'],
            'trialing'    => ['label' => 'Trialing',    'tone' => 'info'],
            'active'      => ['label' => 'Active',      'tone' => 'success'],
            'past_due'    => ['label' => 'Past Due',    'tone' => 'danger'],
            'paused'      => ['label' => 'Paused',      'tone' => 'secondary'],
            'cancelled'   => ['label' => 'Cancelled',   'tone' => 'secondary'],
            'unpaid'      => ['label' => 'Unpaid',      'tone' => 'danger'],
            'expired'     => ['label' => 'Expired',     'tone' => 'secondary'],
            default       => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getIntervalLabelAttribute(): string
    {
        $count = $this->billing_interval_count ?: 1;
        $interval = $this->billing_interval ?: 'month';
        return $count > 1 ? "Every {$count} {$interval}s" : "Every {$interval}";
    }

    public function getIsCancellingAttribute(): bool
    {
        return $this->cancel_at_period_end && in_array($this->status, ['active', 'trialing']);
    }
}
<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'payment_id', 'customer_id', 'initiated_by_id', 'mode',
        'currency', 'amount', 'settlement_amount', 'fee_refunded',
        'is_partial', 'refund_application_fee',
        'reason', 'description', 'status', 'source',
        'payment_provider_id', 'provider_reference', 'failure_code', 'failure_message',
        'idempotency_key', 'processed_at', 'expected_arrival_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'settlement_amount' => 'integer',
        'fee_refunded' => 'integer',
        'is_partial' => 'boolean',
        'refund_application_fee' => 'boolean',
        'processed_at' => 'datetime',
        'expected_arrival_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Refund $r) {
            $r->uuid ??= (string) Str::uuid();
            $r->public_id ??= 'ref_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function initiatedBy() { return $this->belongsTo(User::class, 'initiated_by_id'); }
    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('public_id', 'like', "%{$term}%")
              ->orWhere('provider_reference', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeSucceeded($query) { return $query->where('status', 'succeeded'); }
    public function scopePending($query) { return $query->whereIn('status', ['pending', 'processing']); }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'    => ['label' => 'Pending',    'tone' => 'warning'],
            'processing' => ['label' => 'Processing', 'tone' => 'info'],
            'succeeded'  => ['label' => 'Succeeded',  'tone' => 'success'],
            'failed'     => ['label' => 'Failed',     'tone' => 'danger'],
            'cancelled'  => ['label' => 'Cancelled',  'tone' => 'secondary'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            'requested_by_customer' => 'Requested by Customer',
            'duplicate'             => 'Duplicate',
            'fraudulent'            => 'Fraudulent',
            'expired_uncaptured'    => 'Expired Uncaptured',
            default                 => $this->reason ? ucfirst(str_replace('_', ' ', $this->reason)) : '—',
        };
    }

    public function appliedFees()
    {
        return $this->morphMany(\App\Models\Payment\AppliedFee::class, 'feeable');
    }

    
    public function ledgerTransactions()
    {
        return $this->morphMany(\App\Models\Payment\LedgerTransaction::class, 'source');
    }

    public function balanceTransactions()
    {
        return $this->morphMany(\App\Models\Payment\BalanceTransaction::class, 'source');
    }

}
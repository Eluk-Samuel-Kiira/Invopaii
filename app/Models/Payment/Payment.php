<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Payment\PaymentMethod;
use App\Models\Invoice\Invoice;
use App\Models\PaymentLink\CheckoutSession;
use App\Models\PaymentLink\PaymentLink;
use App\Models\Reference\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'source', 'customer_id', 'payment_method_id',
        'payment_link_id', 'invoice_id', 'checkout_session_id', 'subscription_id',
        'currency', 'amount', 'amount_captured', 'amount_refunded',
        'amount_disputed', 'amount_tip', 'amount_fee_passed_on',
        'settlement_currency', 'settlement_amount', 'exchange_rate', 'exchange_rate_id',
        'fee_amount', 'tax_on_fee_amount', 'net_amount',
        'status', 'capture_method', 'confirmation_method',
        'payment_method_type', 'card_brand', 'card_last_four', 'card_bin',
        'card_country', 'mobile_network', 'bank_name',
        'payment_provider_id', 'provider_reference', 'provider_authorization_code',
        'acquirer_reference', 'network_transaction_id',
        'three_d_secure_used', 'three_d_secure_result', 'avs_result', 'cvv_result',
        'reference', 'description', 'statement_descriptor',
        'receipt_email', 'receipt_number', 'receipt_url', 'receipt_sent_at',
        'next_action_type', 'next_action', 'return_url',
        'risk_score', 'risk_level', 'requires_manual_review',
        'failure_code', 'failure_message', 'decline_reason', 'attempt_count',
        'is_disputed', 'is_settled', 'available_on', 'payout_id',
        'ip_address', 'user_agent', 'device_fingerprint', 'customer_country', 'idempotency_key',
        'authorized_at', 'captured_at', 'succeeded_at', 'failed_at', 'cancelled_at', 'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'amount_captured' => 'integer',
        'amount_refunded' => 'integer',
        'amount_disputed' => 'integer',
        'amount_tip' => 'integer',
        'amount_fee_passed_on' => 'integer',
        'settlement_amount' => 'integer',
        'exchange_rate' => 'decimal:12',
        'fee_amount' => 'integer',
        'tax_on_fee_amount' => 'integer',
        'net_amount' => 'integer',
        'three_d_secure_used' => 'boolean',
        'requires_manual_review' => 'boolean',
        'attempt_count' => 'integer',
        'risk_score' => 'integer',
        'is_disputed' => 'boolean',
        'is_settled' => 'boolean',
        'next_action' => 'array',
        'metadata' => 'array',
        'available_on' => 'date',
        'receipt_sent_at' => 'datetime',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $p) {
            $p->uuid ??= (string) Str::uuid();
            $p->public_id ??= 'pay_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function paymentMethod() { return $this->belongsTo(PaymentMethod::class); }
    public function paymentLink() { return $this->belongsTo(PaymentLink::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function checkoutSession() { return $this->belongsTo(CheckoutSession::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }
    public function exchangeRate() { return $this->belongsTo(ExchangeRate::class); }

    public function attempts()
    {
        return $this->hasMany(PaymentAttempt::class)->orderBy('attempt_number');
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('public_id', 'like', "%{$term}%")
              ->orWhere('reference', 'like', "%{$term}%")
              ->orWhere('provider_reference', 'like', "%{$term}%")
              ->orWhere('receipt_email', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeSucceeded($query) { return $query->where('status', 'succeeded'); }
    public function scopeFailed($query) { return $query->where('status', 'failed'); }
    public function scopePending($query)
    {
        return $query->whereIn('status', [
            'requires_payment_method', 'requires_confirmation', 'requires_action',
            'processing', 'requires_capture',
        ]);
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'requires_payment_method' => ['label' => 'Needs Payment Method', 'tone' => 'warning'],
            'requires_confirmation'   => ['label' => 'Needs Confirmation',   'tone' => 'warning'],
            'requires_action'         => ['label' => 'Needs Action',         'tone' => 'info'],
            'processing'              => ['label' => 'Processing',           'tone' => 'info'],
            'requires_capture'        => ['label' => 'Requires Capture',     'tone' => 'info'],
            'succeeded'               => ['label' => 'Succeeded',            'tone' => 'success'],
            'partially_refunded'      => ['label' => 'Partially Refunded',   'tone' => 'info'],
            'refunded'                => ['label' => 'Refunded',             'tone' => 'secondary'],
            'failed'                  => ['label' => 'Failed',               'tone' => 'danger'],
            'cancelled'               => ['label' => 'Cancelled',            'tone' => 'secondary'],
            'expired'                 => ['label' => 'Expired',              'tone' => 'secondary'],
            'reversed'                => ['label' => 'Reversed',             'tone' => 'danger'],
            default                   => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getIsRefundableAttribute(): bool
    {
        return in_array($this->status, ['succeeded', 'partially_refunded'])
            && $this->amount_refunded < $this->amount_captured;
    }

    public function getRefundableAmountAttribute(): int
    {
        return max(0, $this->amount_captured - $this->amount_refunded);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class)->orderByDesc('created_at');
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class)->orderByDesc('created_at');
    }

    public function successfulRefunds()
    {
        return $this->hasMany(Refund::class)->where('status', 'succeeded');
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

    public function payout()
    {
        return $this->belongsTo(\App\Models\Payment\Payout::class);
    }

    public function riskAssessments()
    {
        return $this->morphMany(\App\Models\Payment\RiskAssessment::class, 'assessable');
    }

}
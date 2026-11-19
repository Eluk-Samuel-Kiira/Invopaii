<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Company\CompanyBankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'company_bank_account_id', 'initiated_by_id', 'mode',
        'currency', 'gross_amount', 'fee_amount', 'adjustment_amount', 'amount',
        'type', 'method', 'status',
        'destination_name', 'destination_bank', 'destination_last_four',
        'payment_provider_id', 'provider_reference', 'bank_reference', 'statement_descriptor',
        'period_start', 'period_end', 'transaction_count',
        'scheduled_for', 'approved_at', 'submitted_at', 'expected_arrival_date', 'paid_at', 'failed_at',
        'failure_code', 'failure_message', 'is_reversed', 'reversal_reason',
        'requires_approval', 'approved_by_id', 'statement_path', 'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'fee_amount' => 'integer',
        'adjustment_amount' => 'integer',
        'amount' => 'integer',
        'transaction_count' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
        'scheduled_for' => 'datetime',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expected_arrival_date' => 'date',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_reversed' => 'boolean',
        'requires_approval' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payout $p) {
            $p->uuid ??= (string) Str::uuid();
            $p->public_id ??= 'po_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function bankAccount() { return $this->belongsTo(CompanyBankAccount::class, 'company_bank_account_id'); }
    public function initiatedBy() { return $this->belongsTo(User::class, 'initiated_by_id'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by_id'); }
    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }

    public function items()
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceTransactions()
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('public_id', 'like', "%{$term}%")
              ->orWhere('provider_reference', 'like', "%{$term}%")
              ->orWhere('bank_reference', 'like', "%{$term}%");
        });
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeInFlight($query) { return $query->whereIn('status', ['approved', 'processing', 'in_transit']); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'    => ['label' => 'Pending',    'tone' => 'warning'],
            'approved'   => ['label' => 'Approved',   'tone' => 'info'],
            'processing' => ['label' => 'Processing', 'tone' => 'info'],
            'in_transit' => ['label' => 'In Transit', 'tone' => 'primary'],
            'paid'       => ['label' => 'Paid',       'tone' => 'success'],
            'failed'     => ['label' => 'Failed',     'tone' => 'danger'],
            'cancelled'  => ['label' => 'Cancelled',  'tone' => 'secondary'],
            'reversed'   => ['label' => 'Reversed',   'tone' => 'danger'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'scheduled' => ['label' => 'Scheduled', 'tone' => 'info'],
            'manual'    => ['label' => 'Manual',    'tone' => 'warning'],
            'instant'   => ['label' => 'Instant',   'tone' => 'success'],
            default     => ['label' => ucfirst($this->type), 'tone' => 'secondary'],
        };
    }

    public function getIsPendingAttribute(): bool { return $this->status === 'pending'; }
    public function getIsCompletedAttribute(): bool { return in_array($this->status, ['paid', 'reversed']); }
    public function getIsFailedAttribute(): bool { return $this->status === 'failed'; }
}
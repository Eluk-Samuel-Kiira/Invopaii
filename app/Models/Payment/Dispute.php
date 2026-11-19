<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'payment_id', 'customer_id', 'mode',
        'currency', 'amount', 'fee_amount',
        'type', 'reason', 'reason_code', 'network', 'status',
        'is_charge_refundable', 'funds_withdrawn', 'funds_reinstated',
        'payment_provider_id', 'provider_reference', 'acquirer_reference',
        'opened_at', 'evidence_due_by', 'responded_at', 'resolved_at', 'resolution_notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'fee_amount' => 'integer',
        'is_charge_refundable' => 'boolean',
        'funds_withdrawn' => 'boolean',
        'funds_reinstated' => 'boolean',
        'opened_at' => 'datetime',
        'evidence_due_by' => 'datetime',
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Dispute $d) {
            $d->uuid ??= (string) Str::uuid();
            $d->public_id ??= 'dp_' . Str::lower(Str::random(24));
        });
    }

    /* ---------- Relations ---------- */

    public function company() { return $this->belongsTo(Company::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }

    public function evidence()
    {
        return $this->hasMany(DisputeEvidence::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('public_id', 'like', "%{$term}%")
              ->orWhere('provider_reference', 'like', "%{$term}%")
              ->orWhere('reason', 'like', "%{$term}%");
        });
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            'warning_needs_response', 'needs_response', 'under_review',
        ]);
    }

    public function scopeNeedingResponse($query)
    {
        return $query->where('status', 'needs_response');
    }

    public function scopeDueSoon($query, int $hours = 72)
    {
        return $query->open()
            ->whereNotNull('evidence_due_by')
            ->where('evidence_due_by', '<=', now()->addHours($hours));
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'warning_needs_response' => ['label' => 'Warning',        'tone' => 'warning'],
            'needs_response'         => ['label' => 'Needs Response', 'tone' => 'danger'],
            'under_review'           => ['label' => 'Under Review',   'tone' => 'info'],
            'won'                    => ['label' => 'Won',            'tone' => 'success'],
            'lost'                   => ['label' => 'Lost',           'tone' => 'danger'],
            'accepted'               => ['label' => 'Accepted',       'tone' => 'warning'],
            'expired'                => ['label' => 'Expired',        'tone' => 'secondary'],
            'charge_refunded'        => ['label' => 'Refunded',       'tone' => 'secondary'],
            default                  => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'inquiry'         => 'Inquiry',
            'retrieval'       => 'Retrieval',
            'chargeback'      => 'Chargeback',
            'pre_arbitration' => 'Pre-Arbitration',
            'arbitration'     => 'Arbitration',
            default           => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['warning_needs_response', 'needs_response', 'under_review']);
    }

    public function getHoursUntilDueAttribute(): ?int
    {
        if (!$this->evidence_due_by || !$this->is_open) return null;
        return max(0, (int) now()->diffInHours($this->evidence_due_by, false));
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
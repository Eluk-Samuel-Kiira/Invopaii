<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BalanceTransaction extends Model
{
    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'balance_id', 'mode',
        'type', 'source_type', 'source_id',
        'currency', 'gross_amount', 'fee_amount', 'net_amount',
        'available_balance_after', 'pending_balance_after',
        'status', 'available_on', 'description', 'reference',
        'payout_id', 'is_reconciled', 'reconciled_at', 'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'fee_amount' => 'integer',
        'net_amount' => 'integer',
        'available_balance_after' => 'integer',
        'pending_balance_after' => 'integer',
        'available_on' => 'date',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (BalanceTransaction $t) {
            $t->uuid ??= (string) Str::uuid();
            $t->public_id ??= 'txn_' . Str::lower(Str::random(24));
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function balance() { return $this->belongsTo(Balance::class); }
    public function source() { return $this->morphTo(); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeAvailable($query) { return $query->where('status', 'available'); }
    public function scopeNotReconciled($query) { return $query->where('is_reconciled', false); }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'   => ['label' => 'Pending',   'tone' => 'warning'],
            'available' => ['label' => 'Available', 'tone' => 'success'],
            'reserved'  => ['label' => 'Reserved',  'tone' => 'info'],
            'paid'      => ['label' => 'Paid',      'tone' => 'secondary'],
            'reversed'  => ['label' => 'Reversed',  'tone' => 'danger'],
            default     => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function payout()
    {
        return $this->belongsTo(\App\Models\Payment\Payout::class);
    }

}
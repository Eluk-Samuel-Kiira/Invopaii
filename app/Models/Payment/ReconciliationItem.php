<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class ReconciliationItem extends Model
{
    protected $fillable = [
        'provider_settlement_id', 'payment_id', 'refund_id',
        'provider_reference', 'currency',
        'provider_amount', 'internal_amount', 'variance', 'status', 'notes', 'raw_row',
    ];

    protected $casts = [
        'provider_amount' => 'integer',
        'internal_amount' => 'integer',
        'variance' => 'integer',
        'raw_row' => 'array',
    ];

    public function settlement() { return $this->belongsTo(ProviderSettlement::class, 'provider_settlement_id'); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function refund() { return $this->belongsTo(Refund::class); }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'matched'           => ['label' => 'Matched',           'tone' => 'success'],
            'unmatched'         => ['label' => 'Unmatched',         'tone' => 'warning'],
            'variance'          => ['label' => 'Variance',          'tone' => 'danger'],
            'missing_internal'  => ['label' => 'Missing Internal',  'tone' => 'danger'],
            'missing_provider'  => ['label' => 'Missing Provider',  'tone' => 'warning'],
            default             => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }
}
<?php

namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProviderSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'payment_provider_id', 'mode',
        'provider_reference', 'currency',
        'gross_amount', 'fee_amount', 'refund_amount', 'chargeback_amount', 'net_amount',
        'transaction_count', 'settlement_date', 'period_start', 'period_end',
        'status', 'expected_amount', 'variance_amount', 'discrepancy_notes',
        'report_path', 'reconciled_at', 'reconciled_by_id',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'fee_amount' => 'integer',
        'refund_amount' => 'integer',
        'chargeback_amount' => 'integer',
        'net_amount' => 'integer',
        'expected_amount' => 'integer',
        'variance_amount' => 'integer',
        'transaction_count' => 'integer',
        'settlement_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'reconciled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($s) => $s->uuid ??= (string) Str::uuid());
    }

    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }
    public function reconciledBy() { return $this->belongsTo(User::class, 'reconciled_by_id'); }

    public function reconciliationItems()
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'     => ['label' => 'Pending',     'tone' => 'warning'],
            'received'    => ['label' => 'Received',    'tone' => 'info'],
            'reconciled'  => ['label' => 'Reconciled',  'tone' => 'success'],
            'discrepancy' => ['label' => 'Discrepancy', 'tone' => 'danger'],
            default       => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getHasVarianceAttribute(): bool
    {
        return $this->variance_amount !== 0;
    }
}
<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'payment_id', 'company_id', 'payment_provider_id', 'mode',
        'attempt_number', 'operation', 'status',
        'amount', 'currency',
        'provider_reference', 'provider_status', 'provider_code', 'provider_message',
        'request_payload', 'response_payload',
        'duration_ms', 'is_fallback', 'is_retry', 'failure_reason',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'amount' => 'integer',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'duration_ms' => 'integer',
        'is_fallback' => 'boolean',
        'is_retry' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($a) => $a->uuid ??= (string) Str::uuid());
    }

    /* ---------- Relations ---------- */

    public function payment() { return $this->belongsTo(Payment::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'initiated' => ['label' => 'Initiated', 'tone' => 'secondary'],
            'pending'   => ['label' => 'Pending',   'tone' => 'info'],
            'succeeded' => ['label' => 'Succeeded', 'tone' => 'success'],
            'failed'    => ['label' => 'Failed',    'tone' => 'danger'],
            'timeout'   => ['label' => 'Timeout',   'tone' => 'warning'],
            default     => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getOperationLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->operation));
    }
}
<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProviderWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'payment_provider_id', 'mode',
        'event_type', 'provider_event_id', 'provider_reference',
        'signature', 'signature_valid',
        'headers', 'payload', 'status', 'process_attempts', 'processing_error',
        'processed_at', 'ip_address',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'headers' => 'array',
        'payload' => 'array',
        'process_attempts' => 'integer',
        'processed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($e) => $e->uuid ??= (string) Str::uuid());
    }

    public function provider() { return $this->belongsTo(PaymentProvider::class, 'payment_provider_id'); }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'received'   => ['label' => 'Received',   'tone' => 'info'],
            'processed'  => ['label' => 'Processed',  'tone' => 'success'],
            'ignored'    => ['label' => 'Ignored',    'tone' => 'secondary'],
            'failed'     => ['label' => 'Failed',     'tone' => 'danger'],
            'duplicate'  => ['label' => 'Duplicate',  'tone' => 'warning'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }
}
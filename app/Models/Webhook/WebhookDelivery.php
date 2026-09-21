<?php

namespace App\Models\Webhook;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'webhook_endpoint_id', 'event_id', 'company_id', 'mode',
        'event_type', 'status', 'attempt',
        'response_code', 'response_body', 'response_headers', 'duration_ms',
        'error_class', 'error_message',
        'scheduled_for', 'next_retry_at', 'delivered_at', 'is_manual_retry',
    ];

    protected $casts = [
        'response_headers' => 'array',
        'attempt' => 'integer',
        'response_code' => 'integer',
        'duration_ms' => 'integer',
        'scheduled_for' => 'datetime',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_manual_retry' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($d) => $d->uuid ??= (string) Str::uuid());
    }

    public function endpoint()
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'    => ['label' => 'Pending',    'tone' => 'info'],
            'delivering' => ['label' => 'Delivering', 'tone' => 'primary'],
            'succeeded'  => ['label' => 'Succeeded',  'tone' => 'success'],
            'failed'     => ['label' => 'Failed',     'tone' => 'warning'],
            'abandoned'  => ['label' => 'Abandoned',  'tone' => 'danger'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    /**
     * Exponential backoff schedule (seconds) for retries.
     * Attempt 1 → immediate, 2 → 30s, 3 → 5m, 4 → 30m, 5 → 2h, 6 → 6h, 7 → 12h, 8 → 24h.
     */
    public static function retryDelaySeconds(int $attempt): int
    {
        return match (true) {
            $attempt <= 1 => 0,
            $attempt === 2 => 30,
            $attempt === 3 => 300,
            $attempt === 4 => 1800,
            $attempt === 5 => 7200,
            $attempt === 6 => 21600,
            $attempt === 7 => 43200,
            default        => 86400,
        };
    }
}
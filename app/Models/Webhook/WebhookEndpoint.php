<?php

namespace App\Models\Webhook;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'url', 'description', 'secret', 'enabled_events', 'api_version',
        'status', 'consecutive_failures',
        'last_success_at', 'last_failure_at', 'disabled_at', 'disabled_reason',
        'timeout_seconds', 'max_attempts', 'custom_headers', 'metadata',
    ];

    protected $casts = [
        'enabled_events' => 'array',
        'custom_headers' => 'array',
        'metadata' => 'array',
        'consecutive_failures' => 'integer',
        'timeout_seconds' => 'integer',
        'max_attempts' => 'integer',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    protected $hidden = ['secret'];

    protected static function booted(): void
    {
        static::creating(function (WebhookEndpoint $e) {
            $e->uuid ??= (string) Str::uuid();
            $e->public_id ??= 'whe_' . Str::lower(Str::random(24));

            // Auto-generate secret if not set — plaintext stored (encrypted cast on secret column)
            if (empty($e->secret)) {
                $e->secret = 'whsec_' . Str::random(48);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'enabled');
    }

    public function scopeForMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'enabled'       => ['label' => 'Enabled',       'tone' => 'success'],
            'disabled'      => ['label' => 'Disabled',      'tone' => 'secondary'],
            'auto_disabled' => ['label' => 'Auto-disabled', 'tone' => 'danger'],
            default         => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getMaskedSecretAttribute(): string
    {
        return 'whsec_' . str_repeat('•', 12) . substr($this->secret ?: '', -4);
    }

    public function isSubscribedTo(string $eventType): bool
    {
        $events = $this->enabled_events ?? [];
        if (in_array('*', $events, true)) return true;
        return in_array($eventType, $events, true);
    }

    /**
     * Compute the HMAC signature for a webhook body.
     * Header: "Stardena-Signature: t=<unix>,v1=<hex>"
     */
    public function signPayload(string $body, int $timestamp): string
    {
        $signed = $timestamp . '.' . $body;
        $hex = hash_hmac('sha256', $signed, $this->secret);
        return "t={$timestamp},v1={$hex}";
    }
}
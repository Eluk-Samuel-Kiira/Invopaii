<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'company_id', 'api_key_id', 'mode',
        'method', 'path', 'route_name', 'api_version',
        'status_code', 'duration_ms',
        'ip_address', 'user_agent', 'idempotency_key', 'request_id',
        'request_headers', 'request_body', 'response_body',
        'error_code', 'error_message',
        'resource_type', 'resource_id', 'created_at',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime',
        'status_code' => 'integer',
        'duration_ms' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($l) => $l->uuid ??= (string) Str::uuid());
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function getStatusBadgeAttribute(): array
    {
        $code = (int) $this->status_code;
        if ($code >= 500) return ['label' => (string) $code, 'tone' => 'danger'];
        if ($code >= 400) return ['label' => (string) $code, 'tone' => 'warning'];
        if ($code >= 200 && $code < 300) return ['label' => (string) $code, 'tone' => 'success'];
        return ['label' => (string) $code, 'tone' => 'secondary'];
    }
}
<?php

namespace App\Models\Platform;

use App\Models\Company\Company;
use App\Models\Payment\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'company_id', 'user_id', 'api_key_id', 'mode',
        'action', 'actor_type', 'actor_label',
        'auditable_type', 'auditable_id', 'resource_public_id',
        'old_values', 'new_values', 'description',
        'ip_address', 'user_agent', 'request_id', 'is_sensitive',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($l) => $l->uuid ??= (string) Str::uuid());
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function apiKey() { return $this->belongsTo(ApiKey::class); }
    public function auditable() { return $this->morphTo(); }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('action', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('actor_label', 'like', "%{$term}%")
              ->orWhere('resource_public_id', 'like', "%{$term}%");
        });
    }

    public function getActorTypeLabelAttribute(): string
    {
        return match ($this->actor_type) {
            'user'    => 'User',
            'api'     => 'API',
            'system'  => 'System',
            'support' => 'Support',
            default   => ucfirst($this->actor_type),
        };
    }

    public static function record(array $data): self
    {
        return static::create([
            'company_id' => $data['company_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'api_key_id' => $data['api_key_id'] ?? null,
            'mode' => $data['mode'] ?? null,
            'action' => $data['action'],
            'actor_type' => $data['actor_type'] ?? 'user',
            'actor_label' => $data['actor_label'] ?? auth()->user()?->email,
            'auditable_type' => $data['auditable_type'] ?? null,
            'auditable_id' => $data['auditable_id'] ?? null,
            'resource_public_id' => $data['resource_public_id'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'description' => $data['description'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'request_id' => $data['request_id'] ?? null,
            'is_sensitive' => $data['is_sensitive'] ?? false,
            'created_at' => now(),
        ]);
    }
}
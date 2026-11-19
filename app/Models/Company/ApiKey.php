<?php

namespace App\Models\Company;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'company_id', 'created_by_id',
        'name', 'mode', 'type',
        'key_prefix', 'key_hash', 'last_four',
        'scopes', 'allowed_ips', 'rate_limit_per_minute',
        'last_used_at', 'last_used_ip',
        'expires_at', 'revoked_at', 'revoked_by_id',
    ];

    protected $casts = [
        'scopes' => 'array',
        'allowed_ips' => 'array',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($k) => $k->uuid ??= (string) Str::uuid());
    }

    /* ---------- Relations ---------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by_id');
    }

    public function requestLogs()
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    /* ---------- Accessors ---------- */

    public function getIsActiveAttribute(): bool
    {
        if ($this->revoked_at) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at) return 'revoked';
        if ($this->expires_at && $this->expires_at->isPast()) return 'expired';
        return 'active';
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active'  => ['label' => 'Active',  'tone' => 'success'],
            'expired' => ['label' => 'Expired', 'tone' => 'warning'],
            'revoked' => ['label' => 'Revoked', 'tone' => 'danger'],
            default   => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getModeBadgeAttribute(): array
    {
        return $this->mode === 'live'
            ? ['label' => 'Live', 'tone' => 'danger']
            : ['label' => 'Test', 'tone' => 'info'];
    }

    public function getMaskedKeyAttribute(): string
    {
        return $this->key_prefix . str_repeat('•', 12) . $this->last_four;
    }

    /* ---------- Static helpers ---------- */

    /**
     * Generate a fresh key pair (plaintext + model attributes) without persisting.
     */
    public static function generate(string $mode, string $type): array
    {
        $prefix = self::buildPrefix($mode, $type);
        $random = Str::random(32);
        $plaintext = $prefix . $random;

        return [
            'plaintext' => $plaintext,
            'attributes' => [
                'mode' => $mode,
                'type' => $type,
                'key_prefix' => $prefix,
                'key_hash' => hash('sha256', $plaintext),
                'last_four' => substr($plaintext, -4),
            ],
        ];
    }

    protected static function buildPrefix(string $mode, string $type): string
    {
        $short = match ($type) {
            'publishable' => 'pk',
            'secret'      => 'sk',
            'restricted'  => 'rk',
            default       => 'ak',
        };
        return "{$short}_{$mode}_";
    }

    /**
     * Look up a key by its plaintext value.
     */
    public static function findByPlaintext(string $plaintext): ?self
    {
        return self::where('key_hash', hash('sha256', $plaintext))->first();
    }

    public function auditLogs()
    {
        return $this->hasMany(\App\Models\Platform\AuditLog::class);
    }
    
}
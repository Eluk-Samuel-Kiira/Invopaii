<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'device_name', 'device_fingerprint',
        'platform', 'browser', 'ip_address', 'location',
        'is_trusted', 'trusted_at', 'last_active_at', 'revoked_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'trusted_at' => 'datetime',
        'last_active_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($d) => $d->uuid ??= (string) Str::uuid());
    }

    public function user() { return $this->belongsTo(User::class); }
}
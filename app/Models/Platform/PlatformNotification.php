<?php

namespace App\Models\Platform;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlatformNotification extends Model
{
    protected $table = 'notifications';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'notifiable_type', 'notifiable_id',
        'company_id', 'category', 'severity', 'data',
        'action_url', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($n) => $n->id ??= (string) Str::uuid());
    }

    public function notifiable() { return $this->morphTo(); }
    public function company() { return $this->belongsTo(Company::class); }

    public function scopeUnread($query) { return $query->whereNull('read_at'); }
    public function scopeRead($query) { return $query->whereNotNull('read_at'); }
    public function scopeForCategory($query, string $category) { return $query->where('category', $category); }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getSeverityBadgeAttribute(): array
    {
        return match ($this->severity) {
            'success'  => ['label' => 'Success',  'tone' => 'success'],
            'warning'  => ['label' => 'Warning',  'tone' => 'warning'],
            'critical' => ['label' => 'Critical', 'tone' => 'danger'],
            default    => ['label' => 'Info',     'tone' => 'info'],
        };
    }
}
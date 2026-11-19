<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Announcement extends Model
{
    protected $fillable = [
        'uuid', 'title', 'body', 'type', 'severity',
        'action_label', 'action_url',
        'target_countries', 'target_company_ids',
        'is_dismissible', 'starts_at', 'ends_at', 'is_published',
    ];

    protected $casts = [
        'target_countries' => 'array',
        'target_company_ids' => 'array',
        'is_dismissible' => 'boolean',
        'is_published' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($a) => $a->uuid ??= (string) Str::uuid());
    }

    public function dismissals() { return $this->hasMany(AnnouncementDismissal::class); }

    public function scopeActive($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'product'     => ['label' => 'Product',     'tone' => 'primary'],
            'incident'    => ['label' => 'Incident',    'tone' => 'danger'],
            'maintenance' => ['label' => 'Maintenance', 'tone' => 'warning'],
            'policy'      => ['label' => 'Policy',      'tone' => 'info'],
            default       => ['label' => ucfirst($this->type), 'tone' => 'secondary'],
        };
    }

    public function isDismissedBy(int $userId): bool
    {
        return $this->dismissals()->where('user_id', $userId)->exists();
    }
}
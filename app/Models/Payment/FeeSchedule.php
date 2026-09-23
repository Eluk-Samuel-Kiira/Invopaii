<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'code', 'description',
        'is_default', 'is_active',
        'effective_from', 'effective_to',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($s) => $s->uuid ??= (string) Str::uuid());
    }

    /* ---------- Relations ---------- */

    public function rules()
    {
        return $this->hasMany(FeeScheduleRule::class)->orderBy('priority');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'fee_schedule_id');
    }

    /* ---------- Scopes ---------- */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('code', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public static function default(): ?self
    {
        return static::active()->where('is_default', true)->first()
            ?? static::active()->orderBy('id')->first();
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        if (!$this->is_active) return ['label' => 'Inactive', 'tone' => 'secondary'];

        $now = now();
        if ($this->effective_from && $this->effective_from->isFuture()) {
            return ['label' => 'Scheduled', 'tone' => 'info'];
        }
        if ($this->effective_to && $this->effective_to->isPast()) {
            return ['label' => 'Expired', 'tone' => 'warning'];
        }

        return ['label' => 'Active', 'tone' => 'success'];
    }

    public function getRulesCountAttribute(): int
    {
        return $this->rules()->count();
    }
}
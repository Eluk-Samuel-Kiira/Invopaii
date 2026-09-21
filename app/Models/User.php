<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\Company\Company;

class User extends Authenticatable
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uuid',
        'name', 'first_name', 'last_name',
        'email', 'avatar', 'phone',
        'password', 'role_id',
        'email_verified_at',
        'magic_link_token', 'magic_link_sent_at', 'magic_link_expires_at',
        'country_code', 'bio', 'is_active', 'last_login_at',

        // new
        'current_company_id', 'current_mode',
        'two_factor_secret', 'two_factor_recovery_codes',
        'two_factor_confirmed_at', 'two_factor_method',
        'password_changed_at', 'failed_login_attempts', 'locked_until',
        'last_login_ip', 'is_platform_admin', 'terms_accepted_at',
        'locale', 'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'magic_link_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'magic_link_sent_at' => 'datetime',
            'magic_link_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
            'failed_login_attempts' => 'integer',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /* ---------- Existing helpers (unchanged) ---------- */

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function setNameAttribute($value): void
    {
        $parts = explode(' ', $value, 2);
        $this->attributes['first_name'] = $parts[0] ?? '';
        $this->attributes['last_name'] = $parts[1] ?? '';
        $this->attributes['name'] = $value;
    }

    public function getProfileCompletionAttribute(): int
    {
        $fields = ['first_name', 'last_name', 'email', 'phone', 'country_code'];
        $completed = 0;
        foreach ($fields as $f) if (!empty($this->$f)) $completed++;
        if (!empty($this->bio)) $completed++;
        return round(($completed / (count($fields) + 1)) * 100);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token && $this->magic_link_expires_at && now()->lt($this->magic_link_expires_at);
    }

    public function generateMagicLinkToken(): string
    {
        $this->forceFill([
            'magic_link_token' => Str::random(64),
            'magic_link_sent_at' => now(),
            'magic_link_expires_at' => now()->addMinutes(15),
        ])->save();
        return $this->magic_link_token;
    }

    public function verifyMagicLinkToken(string $token): bool
    {
        return $this->magic_link_token === $token
            && $this->magic_link_expires_at
            && now()->lt($this->magic_link_expires_at);
    }

    public function clearMagicLinkToken(): void
    {
        $this->forceFill([
            'magic_link_token' => null,
            'magic_link_sent_at' => null,
            'magic_link_expires_at' => null,
        ])->save();
    }

    public function updateLastLogin(): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->save();
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? (str_starts_with($this->avatar, 'http') ? $this->avatar : asset($this->avatar))
            : asset('assets/media/avatars/300-1.jpg');
    }

    /* ---------- New relations ---------- */

    public function currentCompany()
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot(['role', 'permissions', 'can_access_live_mode', 'status', 'last_accessed_at'])
            ->withTimestamps();
    }

    public function ownedCompanies()
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /* ---------- New helpers ---------- */

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }

    public function isPlatformStaff(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    /**
     * The company the user is *actually* operating as right now.
     * Falls back to first active membership if current_company_id is null.
     */
    public function activeCompany(): ?Company
    {
        if ($this->current_company_id) {
            $company = $this->currentCompany;
            if ($company && $this->belongsToCompany($company->id)) {
                return $company;
            }
        }

        return $this->companies()->wherePivot('status', 'active')->first();
    }

    public function belongsToCompany(int $companyId): bool
    {
        return $this->companies()->where('companies.id', $companyId)->exists();
    }
}
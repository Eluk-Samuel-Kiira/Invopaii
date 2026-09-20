<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    /** @use HasFactory<UserFactory> */
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'first_name',
        'last_name',
        'email',
        'avatar',
        'phone',
        'password',
        'role_id',
        'email_verified_at',
        'magic_link_token',
        'magic_link_sent_at',
        'magic_link_expires_at',
        'country_code',
        'bio',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'magic_link_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'magic_link_sent_at' => 'datetime',
            'magic_link_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot function to generate UUID on creating.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Set the user's name from first and last name.
     */
    public function setNameAttribute($value): void
    {
        $parts = explode(' ', $value, 2);
        $this->attributes['first_name'] = $parts[0];
        $this->attributes['last_name'] = $parts[1] ?? '';
        $this->attributes['name'] = $value;
    }

    /**
     * Calculate profile completion percentage.
     */
    public function getProfileCompletionAttribute(): int
    {
        $fields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'country_code'
        ];
        
        $completed = 0;
        $total = count($fields) + 1; // +1 for bio field
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        // Check bio field
        if (!empty($this->bio)) {
            $completed++;
        }
        
        return round(($completed / $total) * 100);
    }

    /**
     * Scope for active users only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if magic link is valid.
     */
    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token && 
               $this->magic_link_expires_at && 
               now()->lt($this->magic_link_expires_at);
    }

    /**
     * Generate a magic link token for the user.
     */
    public function generateMagicLinkToken(): string
    {
        $this->forceFill([
            'magic_link_token' => Str::random(64),
            'magic_link_sent_at' => now(),
            'magic_link_expires_at' => now()->addMinutes(15),
        ])->save();

        return $this->magic_link_token;
    }

    /**
     * Verify magic link token.
     */
    public function verifyMagicLinkToken(string $token): bool
    {
        return $this->magic_link_token === $token && 
               $this->magic_link_expires_at && 
               now()->lt($this->magic_link_expires_at);
    }

    /**
     * Clear magic link token.
     */
    public function clearMagicLinkToken(): void
    {
        $this->forceFill([
            'magic_link_token' => null,
            'magic_link_sent_at' => null,
            'magic_link_expires_at' => null,
        ])->save();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->forceFill([
            'last_login_at' => now(),
        ])->save();
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar 
            ? (str_starts_with($this->avatar, 'http') ? $this->avatar : asset($this->avatar))
            : asset('assets/media/avatars/300-1.jpg');
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

    /** Convenience: the company this user is currently operating as. */
    public function currentCompany(): ?Company
    {
        return $this->companies()->wherePivot('status', 'active')->first();
    }
}
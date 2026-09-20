<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

class CompanyInvitation extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'invited_by_id', 'email', 'role',
        'token', 'status', 'expires_at', 'accepted_at', 'accepted_by_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompanyInvitation $invite) {
            $invite->uuid ??= (string) Str::uuid();
            $invite->token ??= Str::random(64);
            $invite->expires_at ??= now()->addDays(7);
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function invitedBy() { return $this->belongsTo(User::class, 'invited_by_id'); }
    public function acceptedBy() { return $this->belongsTo(User::class, 'accepted_by_id'); }
}
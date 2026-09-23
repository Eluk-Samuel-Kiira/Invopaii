<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'company_id', 'mode',
        'assessable_type', 'assessable_id',
        'score', 'level', 'outcome',
        'triggered_rules', 'signals',
        'provider', 'provider_reference', 'provider_score',
        'manually_reviewed', 'reviewed_by_id', 'review_decision', 'review_notes', 'reviewed_at',
    ];

    protected $casts = [
        'triggered_rules' => 'array',
        'signals' => 'array',
        'score' => 'integer',
        'provider_score' => 'integer',
        'manually_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($a) => $a->uuid ??= (string) Str::uuid());
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function reviewedBy() { return $this->belongsTo(User::class, 'reviewed_by_id'); }
    public function assessable() { return $this->morphTo(); }

    public function getLevelBadgeAttribute(): array
    {
        return match ($this->level) {
            'normal'   => ['label' => 'Normal',   'tone' => 'success'],
            'elevated' => ['label' => 'Elevated', 'tone' => 'warning'],
            'highest'  => ['label' => 'Highest',  'tone' => 'danger'],
            'blocked'  => ['label' => 'Blocked',  'tone' => 'danger'],
            default    => ['label' => ucfirst($this->level), 'tone' => 'secondary'],
        };
    }

    public function getOutcomeBadgeAttribute(): array
    {
        return match ($this->outcome) {
            'allowed'    => ['label' => 'Allowed',    'tone' => 'success'],
            'reviewed'   => ['label' => 'Reviewed',   'tone' => 'info'],
            'blocked'    => ['label' => 'Blocked',    'tone' => 'danger'],
            'challenged' => ['label' => 'Challenged', 'tone' => 'warning'],
            default      => ['label' => ucfirst($this->outcome), 'tone' => 'secondary'],
        };
    }
}
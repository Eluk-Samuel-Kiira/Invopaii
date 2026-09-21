<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerificationCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'checkable_type', 'checkable_id',
        'type', 'provider', 'provider_reference', 'status',
        'score', 'request_payload', 'response_payload',
        'failure_reason', 'completed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'score' => 'integer',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($c) => $c->uuid ??= (string) Str::uuid());
    }

    public function checkable()
    {
        return $this->morphTo();
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'passed'        => ['label' => 'Passed',        'tone' => 'success'],
            'failed'        => ['label' => 'Failed',        'tone' => 'danger'],
            'manual_review' => ['label' => 'Manual Review', 'tone' => 'warning'],
            'errored'       => ['label' => 'Errored',       'tone' => 'danger'],
            'pending'       => ['label' => 'Pending',       'tone' => 'info'],
            default         => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}
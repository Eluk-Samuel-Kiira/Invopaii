<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'uuid', 'public_id', 'company_id',
        'created_by_id', 'approved_by_id', 'mode',
        'direction', 'currency', 'amount', 'category', 'reason',
        'status', 'approved_at', 'applied_at', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (BalanceAdjustment $a) {
            $a->uuid ??= (string) Str::uuid();
            $a->public_id ??= 'adj_' . Str::lower(Str::random(24));
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by_id'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by_id'); }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending'  => ['label' => 'Pending',  'tone' => 'warning'],
            'approved' => ['label' => 'Approved', 'tone' => 'info'],
            'applied'  => ['label' => 'Applied',  'tone' => 'success'],
            'rejected' => ['label' => 'Rejected', 'tone' => 'danger'],
            default    => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'goodwill'           => 'Goodwill',
            'correction'         => 'Correction',
            'penalty'            => 'Penalty',
            'reserve'            => 'Reserve',
            'writeoff'           => 'Write-off',
            'manual_settlement'  => 'Manual Settlement',
            default              => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }
}
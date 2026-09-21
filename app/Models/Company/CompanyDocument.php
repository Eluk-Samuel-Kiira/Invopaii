<?php

namespace App\Models\Company;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompanyDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id', 'company_representative_id',
        'type', 'original_filename', 'disk', 'path',
        'mime_type', 'size_bytes', 'checksum',
        'status', 'reviewed_by_id', 'reviewed_at', 'review_notes', 'expires_on',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'expires_on' => 'date',
        'size_bytes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($d) => $d->uuid ??= (string) Str::uuid());
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function representative()
    {
        return $this->belongsTo(CompanyRepresentative::class, 'company_representative_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'approved' => ['label' => 'Approved', 'tone' => 'success'],
            'pending'  => ['label' => 'Pending',  'tone' => 'info'],
            'rejected' => ['label' => 'Rejected', 'tone' => 'danger'],
            'expired'  => ['label' => 'Expired',  'tone' => 'warning'],
            default    => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getIsPreviewableAttribute(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Human-readable label for document type.
     */
    public function getTypeLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }
}
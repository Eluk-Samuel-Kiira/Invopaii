<?php

namespace App\Models\Company;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompanyRepresentative extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'company_id',
        'first_name', 'last_name', 'email', 'phone',
        'date_of_birth', 'nationality', 'job_title',
        'is_director', 'is_owner', 'is_signatory', 'is_primary_contact',
        'ownership_percent',
        'id_document_type', 'id_document_number', 'id_document_country', 'id_document_expires_on',
        'address_line1', 'city', 'state', 'postal_code', 'country_code',
        'kyc_status', 'pep_check_passed', 'sanctions_check_passed',
        'verified_at', 'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_director' => 'boolean',
        'is_owner' => 'boolean',
        'is_signatory' => 'boolean',
        'is_primary_contact' => 'boolean',
        'ownership_percent' => 'decimal:2',
        'id_document_number' => 'encrypted',       // ← never plaintext at rest
        'id_document_expires_on' => 'date',
        'pep_check_passed' => 'boolean',
        'sanctions_check_passed' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = ['id_document_number'];

    protected static function booted(): void
    {
        static::creating(fn ($r) => $r->uuid ??= (string) Str::uuid());
    }

    /* ---------- Relations ---------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function documents()
    {
        return $this->hasMany(CompanyDocument::class, 'company_representative_id');
    }

    public function verificationChecks()
    {
        return $this->morphMany(VerificationCheck::class, 'checkable');
    }

    /* ---------- Scopes ---------- */

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /* ---------- Accessors ---------- */

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getRolesAttribute(): array
    {
        $roles = [];
        if ($this->is_director) $roles[] = 'Director';
        if ($this->is_owner) $roles[] = 'UBO';
        if ($this->is_signatory) $roles[] = 'Signatory';
        if ($this->is_primary_contact) $roles[] = 'Primary Contact';
        return $roles;
    }

    public function getKycBadgeAttribute(): array
    {
        return match ($this->kyc_status) {
            'verified'   => ['label' => 'Verified',   'tone' => 'success'],
            'pending'    => ['label' => 'Pending',    'tone' => 'info'],
            'rejected'   => ['label' => 'Rejected',   'tone' => 'danger'],
            'unverified' => ['label' => 'Unverified', 'tone' => 'warning'],
            default      => ['label' => ucfirst($this->kyc_status), 'tone' => 'secondary'],
        };
    }

    /**
     * Masked document number for display: ••••••••1234
     */
    public function getMaskedDocumentNumberAttribute(): ?string
    {
        $num = $this->id_document_number;
        if (!$num) return null;
        $len = strlen($num);
        if ($len <= 4) return str_repeat('•', $len);
        return str_repeat('•', $len - 4) . substr($num, -4);
    }
}
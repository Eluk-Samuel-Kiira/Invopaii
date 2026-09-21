<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CompanyBankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id',
        'type', 'currency', 'country_code',
        'account_holder_name', 'account_holder_type',
        'bank_name', 'bank_code', 'branch_code',
        'account_number', 'iban', 'swift_bic', 'routing_number', 'sort_code',
        'mobile_network', 'msisdn',
        'last_four', 'fingerprint',
        'is_default', 'status', 'verification_method',
        'verified_at', 'failure_reason', 'metadata',
    ];

    protected $casts = [
        'account_number' => 'encrypted',
        'iban' => 'encrypted',
        'msisdn' => 'encrypted',
        'is_default' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'account_number', 'iban', 'msisdn', 'fingerprint',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompanyBankAccount $acc) {
            $acc->uuid ??= (string) Str::uuid();
            $acc->public_id ??= 'ba_' . Str::lower(Str::random(24));

            // Derive last_four + fingerprint for search/dedupe
            $acc->last_four = $acc->last_four ?: $acc->deriveLastFour();
            $acc->fingerprint = $acc->fingerprint ?: $acc->deriveFingerprint();
        });

        // Enforce single default per company
        static::saving(function (CompanyBankAccount $acc) {
            if ($acc->is_default && $acc->isDirty('is_default')) {
                static::where('company_id', $acc->company_id)
                    ->where('id', '!=', $acc->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    /* ---------- Relations ---------- */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /* ---------- Scopes ---------- */

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['validated', 'verified']);
    }

    /* ---------- Accessors ---------- */

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'new'        => ['label' => 'New',        'tone' => 'warning'],
            'validated'  => ['label' => 'Validated',  'tone' => 'info'],
            'verified'   => ['label' => 'Verified',   'tone' => 'success'],
            'errored'    => ['label' => 'Errored',    'tone' => 'danger'],
            'disabled'   => ['label' => 'Disabled',   'tone' => 'secondary'],
            default      => ['label' => ucfirst($this->status), 'tone' => 'secondary'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'bank_account'  => 'Bank Account',
            'mobile_money'  => 'Mobile Money',
            'wallet'        => 'Wallet',
            default         => ucfirst($this->type),
        };
    }

    /**
     * Masked account for display: •••• 1234
     */
    public function getMaskedAccountAttribute(): ?string
    {
        if ($this->type === 'mobile_money') {
            $num = $this->msisdn;
            if (!$num) return null;
            $len = strlen($num);
            return str_repeat('•', max(0, $len - 4)) . substr($num, -4);
        }

        return $this->last_four ? '•••• ' . $this->last_four : null;
    }

    /**
     * Human summary: "GTBank •••• 1234 (UGX)"
     */
    public function getDisplayLabelAttribute(): string
    {
        $parts = [];
        if ($this->type === 'bank_account' && $this->bank_name) {
            $parts[] = $this->bank_name;
        } elseif ($this->type === 'mobile_money' && $this->mobile_network) {
            $parts[] = ucfirst($this->mobile_network);
        }
        $parts[] = $this->masked_account ?? '—';
        $parts[] = "({$this->currency})";
        return implode(' ', $parts);
    }

    /* ---------- Helpers ---------- */

    protected function deriveLastFour(): ?string
    {
        $source = match ($this->type) {
            'mobile_money' => $this->msisdn,
            default        => $this->account_number ?: $this->iban,
        };

        if (!$source) return null;
        $clean = preg_replace('/\s+/', '', $source);
        return substr($clean, -4) ?: null;
    }

    protected function deriveFingerprint(): ?string
    {
        $source = match ($this->type) {
            'mobile_money' => $this->msisdn,
            default        => $this->account_number ?: $this->iban,
        };

        if (!$source) return null;

        // HMAC with app key — not a plain hash, so it can't be reversed with a rainbow table
        $clean = preg_replace('/\s+/', '', $source);
        return hash_hmac('sha256', $clean, config('app.key'));
    }

    public function markVerified(?string $method = null): void
    {
        $this->update([
            'status' => 'verified',
            'verification_method' => $method ?: $this->verification_method,
            'verified_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function markErrored(string $reason): void
    {
        $this->update([
            'status' => 'errored',
            'failure_reason' => $reason,
        ]);
    }
}
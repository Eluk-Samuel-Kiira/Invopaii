<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlocklistEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'company_id', 'created_by_id', 'mode',
        'type', 'value', 'value_hash', 'list', 'reason', 'source',
        'hit_count', 'last_hit_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (BlocklistEntry $e) {
            $e->uuid ??= (string) Str::uuid();

            // Auto-hash sensitive values
            if (!$e->value_hash && in_array($e->type, ['email', 'phone', 'card_fingerprint', 'device'])) {
                $e->value_hash = hash_hmac('sha256', strtolower($e->value), config('app.key'));
            }
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by_id'); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function scopeOfType($query, string $type) { return $query->where('type', $type); }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id');
            if ($companyId) $q->orWhere('company_id', $companyId);
        });
    }

    public function scopeBlocking($query) { return $query->where('list', 'block'); }
    public function scopeAllowing($query) { return $query->where('list', 'allow'); }

    public function getListBadgeAttribute(): array
    {
        return match ($this->list) {
            'block'  => ['label' => 'Block',  'tone' => 'danger'],
            'allow'  => ['label' => 'Allow',  'tone' => 'success'],
            'review' => ['label' => 'Review', 'tone' => 'warning'],
            default  => ['label' => ucfirst($this->list), 'tone' => 'secondary'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }
}
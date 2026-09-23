<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LedgerTransaction extends Model
{
    protected $fillable = [
        'uuid', 'mode', 'type',
        'source_type', 'source_id',
        'company_id', 'currency', 'amount', 'description',
        'idempotency_key', 'posted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($t) => $t->uuid ??= (string) Str::uuid());
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function source() { return $this->morphTo(); }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Verify debits equal credits. Throws if unbalanced.
     */
    public function assertBalanced(): void
    {
        $debits = $this->entries()->where('direction', 'debit')->sum('amount');
        $credits = $this->entries()->where('direction', 'credit')->sum('amount');

        if ($debits !== $credits) {
            throw new \RuntimeException(
                "Ledger transaction {$this->uuid} is unbalanced: debits={$debits} credits={$credits}"
            );
        }
    }

    public function getIsBalancedAttribute(): bool
    {
        $debits = $this->entries()->where('direction', 'debit')->sum('amount');
        $credits = $this->entries()->where('direction', 'credit')->sum('amount');
        return $debits === $credits;
    }
}
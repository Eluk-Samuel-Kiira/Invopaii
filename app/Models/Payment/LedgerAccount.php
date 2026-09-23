<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'normal_balance', 'category',
        'company_id', 'currency', 'mode',
        'balance', 'is_active', 'metadata',
    ];

    protected $casts = [
        'balance' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function entries() { return $this->hasMany(LedgerEntry::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeCategory($query, string $category) { return $query->where('category', $category); }

    /**
     * Signed balance — positive when normal_balance direction has grown.
     * For a liability (normal=credit), a positive balance means we owe that amount.
     */
    public function getSignedBalanceAttribute(): int
    {
        return $this->balance;
    }

    /**
     * Retrieve or create an account by its composite code.
     */
    public static function findOrCreateByCode(string $code, array $attributes): self
    {
        return static::firstOrCreate(['code' => $code], $attributes);
    }
}
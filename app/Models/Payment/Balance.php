<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'company_id', 'mode', 'currency',
        'available_amount', 'pending_amount', 'reserved_amount', 'payout_in_transit',
        'lifetime_volume', 'lifetime_fees',
        'last_transaction_at', 'last_reconciled_at',
    ];

    protected $casts = [
        'available_amount' => 'integer',
        'pending_amount' => 'integer',
        'reserved_amount' => 'integer',
        'payout_in_transit' => 'integer',
        'lifetime_volume' => 'integer',
        'lifetime_fees' => 'integer',
        'last_transaction_at' => 'datetime',
        'last_reconciled_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }

    public function transactions()
    {
        return $this->hasMany(BalanceTransaction::class)->orderByDesc('created_at');
    }

    public function getTotalAttribute(): int
    {
        return $this->available_amount
            + $this->pending_amount
            + $this->reserved_amount
            + $this->payout_in_transit;
    }

    public function getIsWithdrawableAttribute(): bool
    {
        return $this->available_amount > 0;
    }

    public static function forCompany(int $companyId, string $mode, string $currency): self
    {
        return static::firstOrCreate(
            ['company_id' => $companyId, 'mode' => $mode, 'currency' => $currency],
            ['available_amount' => 0, 'pending_amount' => 0]
        );
    }
}
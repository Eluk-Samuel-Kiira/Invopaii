<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $fillable = [
        'ledger_transaction_id', 'ledger_account_id',
        'direction', 'amount', 'currency',
        'balance_after', 'posted_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'posted_at' => 'datetime',
    ];

    public function transaction() { return $this->belongsTo(LedgerTransaction::class, 'ledger_transaction_id'); }
    public function account() { return $this->belongsTo(LedgerAccount::class, 'ledger_account_id'); }

    public function getIsDebitAttribute(): bool { return $this->direction === 'debit'; }
    public function getIsCreditAttribute(): bool { return $this->direction === 'credit'; }
}
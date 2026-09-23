<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $fillable = [
        'payout_id', 'balance_transaction_id', 'type', 'amount', 'currency',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function payout() { return $this->belongsTo(Payout::class); }
    public function balanceTransaction() { return $this->belongsTo(BalanceTransaction::class); }
}
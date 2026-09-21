<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class CheckoutSessionItem extends Model
{
    protected $fillable = [
        'checkout_session_id', 'name', 'description',
        'unit_amount', 'quantity', 'amount_subtotal',
        'amount_tax', 'amount_total', 'currency',
    ];

    protected $casts = [
        'unit_amount' => 'integer',
        'quantity' => 'integer',
        'amount_subtotal' => 'integer',
        'amount_tax' => 'integer',
        'amount_total' => 'integer',
    ];

    public function checkoutSession() { return $this->belongsTo(CheckoutSession::class); }
}
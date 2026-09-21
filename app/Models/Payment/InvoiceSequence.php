<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class InvoiceSequence extends Model
{
    protected $fillable = ['company_id', 'mode', 'prefix', 'year', 'next_number', 'padding'];

    protected $casts = [
        'year' => 'integer',
        'next_number' => 'integer',
        'padding' => 'integer',
    ];
}
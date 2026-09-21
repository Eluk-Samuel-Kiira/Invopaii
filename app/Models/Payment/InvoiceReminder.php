<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class InvoiceReminder extends Model
{
    protected $fillable = [
        'invoice_id', 'company_id', 'type', 'offset_days',
        'channel', 'status', 'scheduled_for', 'sent_at', 'failure_reason',
    ];

    protected $casts = [
        'offset_days' => 'integer',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function company() { return $this->belongsTo(Company::class); }
}
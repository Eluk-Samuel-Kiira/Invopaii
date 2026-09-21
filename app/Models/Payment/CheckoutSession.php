<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Invoice\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CheckoutSession extends Model
{
    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'payment_link_id', 'invoice_id', 'customer_id', 'payment_id',
        'session_token', 'url',
        'currency', 'amount_subtotal', 'amount_discount', 'amount_tax', 'amount_fee', 'amount_total',
        'customer_name', 'customer_email', 'customer_phone', 'customer_country',
        'billing_address', 'shipping_address', 'custom_field_values',
        'selected_payment_method', 'status', 'payment_status',
        'locale', 'ip_address', 'user_agent', 'device_fingerprint', 'referrer',
        'success_url', 'cancel_url', 'expires_at', 'completed_at', 'metadata',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'custom_field_values' => 'array',
        'metadata' => 'array',
        'amount_subtotal' => 'integer',
        'amount_discount' => 'integer',
        'amount_tax' => 'integer',
        'amount_fee' => 'integer',
        'amount_total' => 'integer',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CheckoutSession $s) {
            $s->uuid ??= (string) Str::uuid();
            $s->public_id ??= 'cs_' . Str::lower(Str::random(24));
            $s->session_token ??= Str::random(64);
        });
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function paymentLink() { return $this->belongsTo(PaymentLink::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(CheckoutSessionItem::class); }
}
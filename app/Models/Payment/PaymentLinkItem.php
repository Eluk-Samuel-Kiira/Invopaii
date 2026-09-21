<?php

namespace App\Models\Payment;

use App\Models\Catalog\Price;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxRate;
use Illuminate\Database\Eloquent\Model;

class PaymentLinkItem extends Model
{
    protected $fillable = [
        'payment_link_id', 'price_id', 'product_id',
        'name', 'description', 'unit_amount', 'currency',
        'quantity', 'min_quantity', 'max_quantity', 'is_adjustable',
        'tax_rate_id', 'sort_order',
    ];

    protected $casts = [
        'unit_amount' => 'integer',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'is_adjustable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function paymentLink() { return $this->belongsTo(PaymentLink::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function price() { return $this->belongsTo(Price::class); }
    public function taxRate() { return $this->belongsTo(TaxRate::class); }
}
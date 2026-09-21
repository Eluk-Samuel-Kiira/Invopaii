<?php

namespace App\Models\Payment;

use App\Models\Catalog\Price;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionItem extends Model
{
    protected $fillable = [
        'uuid', 'subscription_id', 'price_id', 'product_id',
        'name', 'unit_amount', 'currency', 'quantity',
        'tax_rate_id', 'metadata',
    ];

    protected $casts = [
        'unit_amount' => 'integer',
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($i) => $i->uuid ??= (string) Str::uuid());
    }

    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function price() { return $this->belongsTo(Price::class); }
    public function taxRate() { return $this->belongsTo(TaxRate::class); }

    public function getLineTotalAttribute(): int
    {
        return (int) ($this->unit_amount * $this->quantity);
    }
}
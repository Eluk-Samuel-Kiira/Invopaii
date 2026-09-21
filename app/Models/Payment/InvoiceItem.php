<?php

namespace App\Models\Payment;

use App\Models\Catalog\Price;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvoiceItem extends Model
{
    protected $fillable = [
        'uuid', 'invoice_id', 'product_id', 'price_id',
        'name', 'description', 'quantity', 'unit_label',
        'unit_amount', 'currency',
        'discount_amount', 'tax_rate_id', 'tax_percentage', 'tax_amount',
        'subtotal', 'total', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_amount' => 'integer',
        'discount_amount' => 'integer',
        'tax_percentage' => 'decimal:3',
        'tax_amount' => 'integer',
        'subtotal' => 'integer',
        'total' => 'integer',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (InvoiceItem $item) {
            $item->uuid ??= (string) Str::uuid();
        });
    }

    /* ---------- Relations ---------- */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    /* ---------- Accessors ---------- */

    public function getLineSubtotalDisplayAttribute(): string
    {
        return $this->formatMoney($this->subtotal, $this->currency);
    }

    public function getLineTotalDisplayAttribute(): string
    {
        return $this->formatMoney($this->total, $this->currency);
    }

    /**
     * Compute the effective per-unit price after line discount.
     */
    public function getEffectiveUnitAmountAttribute(): int
    {
        $qty = (float) $this->quantity;
        if ($qty <= 0) return $this->unit_amount;

        return (int) round(($this->subtotal - $this->discount_amount) / $qty);
    }

    protected function formatMoney(int $minor, string $currency): string
    {
        $zeroDecimal = ['UGX', 'RWF', 'BIF', 'XOF', 'XAF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'XPF'];
        $amount = in_array($currency, $zeroDecimal, true) ? $minor : $minor / 100;

        try {
            return (new \NumberFormatter('en_US', \NumberFormatter::CURRENCY))
                ->formatCurrency($amount, $currency);
        } catch (\Throwable $e) {
            return $currency . ' ' . number_format($amount, 2);
        }
    }
}
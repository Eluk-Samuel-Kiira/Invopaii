<?php

namespace App\Models\Catalog;

use App\Models\Company\Company;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;

class DiscountRedemption extends Model
{
    protected $fillable = [
        'discount_id', 'company_id', 'customer_id',
        'redeemable_type', 'redeemable_id',
        'amount_discounted', 'currency', 'redeemed_at',
    ];

    protected $casts = [
        'amount_discounted' => 'integer',
        'redeemed_at' => 'datetime',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function redeemable()
    {
        return $this->morphTo();
    }
}
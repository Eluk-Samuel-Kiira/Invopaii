<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerAddress extends Model
{
    protected $fillable = [
        'uuid', 'customer_id', 'type', 'name',
        'line1', 'line2', 'city', 'state', 'postal_code', 'country_code',
        'phone', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn ($a) => $a->uuid ??= (string) Str::uuid());

        // Enforce single default per (customer, type)
        static::saving(function (CustomerAddress $a) {
            if ($a->is_default && $a->isDirty('is_default')) {
                static::where('customer_id', $a->customer_id)
                    ->where('type', $a->type)
                    ->where('id', '!=', $a->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getOneLineAttribute(): string
    {
        return implode(', ', array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_code,
        ]));
    }
}
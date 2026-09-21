<?php

namespace App\Models\Catalog;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'name', 'description', 'image_path', 'sku', 'unit_label',
        'is_active', 'is_shippable', 'tax_code', 'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_shippable' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $p) {
            $p->uuid ??= (string) Str::uuid();
            $p->public_id ??= 'prod_' . Str::lower(Str::random(24));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function defaultPrice()
    {
        return $this->hasOne(Price::class)->where('is_active', true)->orderBy('unit_amount');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
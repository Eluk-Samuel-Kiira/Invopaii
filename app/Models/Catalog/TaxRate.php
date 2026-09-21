<?php

namespace App\Models\Catalog;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'public_id', 'company_id', 'mode',
        'display_name', 'percentage', 'is_inclusive',
        'country_code', 'state', 'jurisdiction', 'tax_type',
        'description', 'is_active', 'metadata',
    ];

    protected $casts = [
        'percentage' => 'decimal:3',
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (TaxRate $t) {
            $t->uuid ??= (string) Str::uuid();
            $t->public_id ??= 'txr_' . Str::lower(Str::random(24));
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->display_name} ({$this->percentage}%)";
    }
}
<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VelocityLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'mode', 'scope', 'window', 'currency',
        'max_count', 'max_amount', 'action', 'is_active',
    ];

    protected $casts = [
        'max_count' => 'integer',
        'max_amount' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id');
            if ($companyId) $q->orWhere('company_id', $companyId);
        });
    }

    public function getWindowSecondsAttribute(): int
    {
        return match ($this->window) {
            'minute' => 60,
            'hour'   => 3600,
            'day'    => 86400,
            'week'   => 604800,
            'month'  => 2592000,
            default  => 3600,
        };
    }

    public function getSummaryAttribute(): string
    {
        $parts = [];
        if ($this->max_count) $parts[] = "max {$this->max_count} txns";
        if ($this->max_amount) $parts[] = "max {$this->max_amount} {$this->currency}";
        return implode(' / ', $parts) . ' per ' . $this->window;
    }
}
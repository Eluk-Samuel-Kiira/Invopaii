<?php

namespace App\Models\Platform;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_id', 'mode', 'group', 'key', 'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }

    public static function get(int $companyId, string $group, string $key, $default = null, ?string $mode = null)
    {
        $setting = static::where('company_id', $companyId)
            ->where('group', $group)
            ->where('key', $key)
            ->where(function ($q) use ($mode) {
                if ($mode) {
                    $q->where('mode', $mode)->orWhereNull('mode');
                } else {
                    $q->whereNull('mode');
                }
            })
            ->orderByRaw('mode IS NULL ASC')
            ->first();

        return $setting?->value ?? $default;
    }

    public static function set(int $companyId, string $group, string $key, $value, ?string $mode = null): self
    {
        return static::updateOrCreate(
            ['company_id' => $companyId, 'mode' => $mode, 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }
}
<?php

namespace App\Models\Payment;

use App\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RiskRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'company_id', 'mode',
        'name', 'description', 'scope', 'conditions', 'action',
        'score_weight', 'priority', 'is_active',
        'times_triggered', 'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'score_weight' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'times_triggered' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($r) => $r->uuid ??= (string) Str::uuid());
    }

    public function company() { return $this->belongsTo(Company::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }

    public function scopeForScope($query, string $scope) { return $query->where('scope', $scope); }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id');
            if ($companyId) $q->orWhere('company_id', $companyId);
        });
    }

    /**
     * Evaluate this rule's conditions against an attribute bag.
     * Returns true if ALL conditions match (AND).
     */
    public function matches(array $attributes): bool
    {
        foreach ($this->conditions ?? [] as $condition) {
            if (!$this->evaluateCondition($condition, $attributes)) {
                return false;
            }
        }
        return true;
    }

    protected function evaluateCondition(array $condition, array $attributes): bool
    {
        $field = $condition['field'] ?? null;
        $op = $condition['operator'] ?? 'eq';
        $expected = $condition['value'] ?? null;

        if (!$field) return false;

        $actual = data_get($attributes, $field);

        return match ($op) {
            'eq'            => $actual == $expected,
            'ne'            => $actual != $expected,
            'gt'            => $actual > $expected,
            'gte'           => $actual >= $expected,
            'lt'            => $actual < $expected,
            'lte'           => $actual <= $expected,
            'in'            => in_array($actual, (array) $expected),
            'not_in'        => !in_array($actual, (array) $expected),
            'contains'      => is_string($actual) && str_contains($actual, (string) $expected),
            'starts_with'   => is_string($actual) && str_starts_with($actual, (string) $expected),
            'is_null'       => is_null($actual),
            'not_null'      => !is_null($actual),
            default         => false,
        };
    }

    public function getActionBadgeAttribute(): array
    {
        return match ($this->action) {
            'allow'        => ['label' => 'Allow',       'tone' => 'success'],
            'review'       => ['label' => 'Review',      'tone' => 'warning'],
            'block'        => ['label' => 'Block',       'tone' => 'danger'],
            'require_3ds'  => ['label' => 'Require 3DS', 'tone' => 'info'],
            'challenge'    => ['label' => 'Challenge',   'tone' => 'info'],
            default        => ['label' => ucfirst($this->action), 'tone' => 'secondary'],
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return $this->is_active
            ? ['label' => 'Active', 'tone' => 'success']
            : ['label' => 'Inactive', 'tone' => 'secondary'];
    }
}
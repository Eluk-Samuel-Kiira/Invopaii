<?php

namespace App\Services\Payment;

use App\Models\Customer\Customer;
use App\Models\Payment\BlocklistEntry;
use App\Models\Payment\Payment;
use App\Models\Payment\RiskAssessment;
use App\Models\Payment\RiskRule;
use App\Models\Payment\VelocityLimit;
use Illuminate\Database\Eloquent\Model;

class RiskService
{
    /**
     * Assess a payment (or any assessable) against active rules.
     * Returns a RiskAssessment with level + outcome.
     */
    public static function assess(
        int $companyId,
        string $mode,
        array $attributes,
        ?Model $assessable = null
    ): RiskAssessment {
        // 1. Blocklist check (short-circuits)
        if ($blocklist = self::checkBlocklist($companyId, $mode, $attributes)) {
            return self::recordAssessment($companyId, $mode, $assessable, [
                'score' => 100,
                'level' => 'blocked',
                'outcome' => 'blocked',
                'signals' => ['blocklist_hit' => $blocklist->type . '=' . $blocklist->value],
            ]);
        }

        // 2. Velocity check
        $velocityViolation = self::checkVelocity($companyId, $mode, $attributes);
        if ($velocityViolation) {
            return self::recordAssessment($companyId, $mode, $assessable, [
                'score' => 90,
                'level' => 'highest',
                'outcome' => $velocityViolation['action'] === 'block' ? 'blocked' : 'reviewed',
                'signals' => ['velocity_violation' => $velocityViolation],
            ]);
        }

        // 3. Rule-based scoring
        $rules = RiskRule::active()
            ->forScope('payment')
            ->forCompany($companyId)
            ->orderBy('priority')
            ->get();

        $score = 0;
        $triggered = [];
        $actionTaken = 'allow';

        foreach ($rules as $rule) {
            if ($rule->mode && $rule->mode !== $mode) continue;

            if ($rule->matches($attributes)) {
                $score += $rule->score_weight;
                $triggered[] = [
                    'rule_id' => $rule->id,
                    'name' => $rule->name,
                    'action' => $rule->action,
                    'weight' => $rule->score_weight,
                ];

                // Priority-based action: earlier rules with action != allow take precedence
                if ($rule->action === 'block' && $actionTaken !== 'block') {
                    $actionTaken = 'block';
                } elseif ($rule->action === 'challenge' && $actionTaken === 'allow') {
                    $actionTaken = 'challenge';
                } elseif ($rule->action === 'review' && $actionTaken === 'allow') {
                    $actionTaken = 'review';
                }

                $rule->increment('times_triggered');
                $rule->update(['last_triggered_at' => now()]);
            }
        }

        // Determine outcome from score + explicit actions
        $level = self::scoreToLevel($score);
        $outcome = match (true) {
            $actionTaken === 'block' => 'blocked',
            $actionTaken === 'review' => 'reviewed',
            $actionTaken === 'challenge' => 'challenged',
            $score >= 70 => 'reviewed',
            $score >= 40 => 'challenged',
            default => 'allowed',
        };

        return self::recordAssessment($companyId, $mode, $assessable, [
            'score' => min($score, 100),
            'level' => $level,
            'outcome' => $outcome,
            'triggered_rules' => $triggered,
            'signals' => self::extractSignals($attributes),
        ]);
    }

    /**
     * Should a payment be allowed to proceed?
     */
    public static function shouldBlock(RiskAssessment $assessment): bool
    {
        return $assessment->outcome === 'blocked';
    }

    public static function requiresReview(RiskAssessment $assessment): bool
    {
        return in_array($assessment->outcome, ['reviewed', 'challenged']);
    }

    /* ═══════════════════════════════════════════════════════
       CHECKS
       ═══════════════════════════════════════════════════════ */

    public static function checkBlocklist(int $companyId, string $mode, array $attributes): ?BlocklistEntry
    {
        $checks = [
            'email' => $attributes['customer_email'] ?? $attributes['receipt_email'] ?? null,
            'ip' => $attributes['ip_address'] ?? null,
            'card_fingerprint' => $attributes['card_fingerprint'] ?? null,
            'card_bin' => $attributes['card_bin'] ?? null,
            'phone' => $attributes['customer_phone'] ?? null,
            'country' => $attributes['customer_country'] ?? null,
        ];

        foreach ($checks as $type => $value) {
            if (!$value) continue;

            $query = BlocklistEntry::active()
                ->ofType($type)
                ->blocking()
                ->forCompany($companyId);

            if (in_array($type, ['email', 'phone', 'card_fingerprint', 'device'])) {
                $hash = hash_hmac('sha256', strtolower((string) $value), config('app.key'));
                $query->where(function ($q) use ($value, $hash) {
                    $q->where('value', $value)->orWhere('value_hash', $hash);
                });
            } else {
                $query->where('value', $value);
            }

            $entry = $query->first();
            if ($entry) {
                $entry->increment('hit_count');
                $entry->update(['last_hit_at' => now()]);
                return $entry;
            }
        }

        return null;
    }

    public static function checkVelocity(int $companyId, string $mode, array $attributes): ?array
    {
        $limits = VelocityLimit::active()->forCompany($companyId)->get();

        foreach ($limits as $limit) {
            if ($limit->mode && $limit->mode !== $mode) continue;

            $since = now()->subSeconds($limit->window_seconds);

            $query = Payment::where('company_id', $companyId)
                ->where('created_at', '>=', $since)
                ->whereNotIn('status', ['failed', 'cancelled', 'expired']);

            switch ($limit->scope) {
                case 'customer':
                    if (!empty($attributes['customer_id'])) {
                        $query->where('customer_id', $attributes['customer_id']);
                    } else {
                        continue 2;
                    }
                    break;

                case 'card':
                    if (!empty($attributes['card_fingerprint'])) {
                        $query->where('card_bin', $attributes['card_bin'] ?? null);
                    } else {
                        continue 2;
                    }
                    break;

                case 'ip':
                    if (!empty($attributes['ip_address'])) {
                        $query->where('ip_address', $attributes['ip_address']);
                    } else {
                        continue 2;
                    }
                    break;

                case 'email':
                    if (!empty($attributes['receipt_email'])) {
                        $query->where('receipt_email', $attributes['receipt_email']);
                    } else {
                        continue 2;
                    }
                    break;

                case 'company':
                    // No additional scoping — company-wide
                    break;

                default:
                    continue 2;
            }

            if ($limit->max_count !== null) {
                $count = $query->count();
                if ($count >= $limit->max_count) {
                    return [
                        'limit_id' => $limit->id,
                        'scope' => $limit->scope,
                        'window' => $limit->window,
                        'reason' => "Exceeded max_count {$limit->max_count}",
                        'action' => $limit->action,
                        'actual' => $count,
                    ];
                }
            }

            if ($limit->max_amount !== null) {
                $sum = (int) $query->sum('amount');
                if ($sum + ($attributes['amount'] ?? 0) > $limit->max_amount) {
                    return [
                        'limit_id' => $limit->id,
                        'scope' => $limit->scope,
                        'window' => $limit->window,
                        'reason' => "Exceeded max_amount {$limit->max_amount}",
                        'action' => $limit->action,
                        'actual' => $sum,
                    ];
                }
            }
        }

        return null;
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected static function scoreToLevel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'blocked',
            $score >= 60 => 'highest',
            $score >= 30 => 'elevated',
            default      => 'normal',
        };
    }

    protected static function extractSignals(array $attributes): array
    {
        return array_filter([
            'amount' => $attributes['amount'] ?? null,
            'currency' => $attributes['currency'] ?? null,
            'payment_method' => $attributes['payment_method_type'] ?? null,
            'country' => $attributes['customer_country'] ?? null,
            'new_customer' => empty($attributes['customer_id']),
            'ip' => $attributes['ip_address'] ?? null,
        ], fn ($v) => !is_null($v));
    }

    protected static function recordAssessment(
        int $companyId,
        string $mode,
        ?Model $assessable,
        array $data
    ): RiskAssessment {
        return RiskAssessment::create([
            'company_id' => $companyId,
            'mode' => $mode,
            'assessable_type' => $assessable ? get_class($assessable) : null,
            'assessable_id' => $assessable?->id,
            'score' => $data['score'] ?? 0,
            'level' => $data['level'] ?? 'normal',
            'outcome' => $data['outcome'] ?? 'allowed',
            'triggered_rules' => $data['triggered_rules'] ?? null,
            'signals' => $data['signals'] ?? null,
        ]);
    }
}
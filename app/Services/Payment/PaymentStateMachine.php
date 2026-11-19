<?php

namespace App\Services\Payment;

use App\Models\Payment\Payment;

class PaymentStateMachine
{
    /**
     * Legal transitions from each state.
     */
    protected const TRANSITIONS = [
        'requires_payment_method' => ['requires_confirmation', 'requires_action', 'processing', 'failed', 'cancelled', 'expired'],
        'requires_confirmation'   => ['requires_action', 'processing', 'failed', 'cancelled', 'expired'],
        'requires_action'         => ['processing', 'requires_capture', 'failed', 'cancelled', 'expired'],
        'processing'              => ['requires_action', 'requires_capture', 'succeeded', 'failed', 'cancelled', 'expired'],
        'requires_capture'        => ['succeeded', 'failed', 'cancelled', 'expired'],
        'succeeded'               => ['partially_refunded', 'refunded', 'reversed'],
        'partially_refunded'      => ['refunded', 'reversed'],
        'refunded'                => [],
        'failed'                  => ['requires_payment_method'], // retry
        'cancelled'               => [],
        'expired'                 => [],
        'reversed'                => [],
    ];

    /**
     * Which timestamps get set when entering each state.
     */
    protected const TIMESTAMP_MAP = [
        'succeeded' => 'succeeded_at',
        'failed'    => 'failed_at',
        'cancelled' => 'cancelled_at',
    ];

    /**
     * Can the payment move to $newStatus?
     */
    public static function canTransition(Payment $payment, string $newStatus): bool
    {
        if ($payment->status === $newStatus) return true;

        $allowed = self::TRANSITIONS[$payment->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Transition a payment to a new state. Throws if illegal.
     */
    public static function transition(Payment $payment, string $newStatus, array $extraAttributes = []): Payment
    {
        if (!self::canTransition($payment, $newStatus)) {
            throw new \RuntimeException(
                "Illegal payment transition: {$payment->status} → {$newStatus} (payment {$payment->public_id})"
            );
        }

        $attributes = array_merge(['status' => $newStatus], $extraAttributes);

        // Set entry timestamps
        if (isset(self::TIMESTAMP_MAP[$newStatus]) && empty($payment->{self::TIMESTAMP_MAP[$newStatus]})) {
            $attributes[self::TIMESTAMP_MAP[$newStatus]] = now();
        }

        // Special handling per target state
        switch ($newStatus) {
            case 'succeeded':
                if (empty($payment->amount_captured)) {
                    $attributes['amount_captured'] = $payment->amount;
                }
                $attributes['captured_at'] = $payment->captured_at ?? now();
                break;

            case 'failed':
                $attributes['failure_message'] = $extraAttributes['failure_message'] ?? $payment->failure_message;
                $attributes['failure_code'] = $extraAttributes['failure_code'] ?? $payment->failure_code;
                break;
        }

        $payment->fill($attributes)->save();

        return $payment->fresh();
    }

    /**
     * Convenience: mark succeeded with optional extra data.
     */
    public static function markSucceeded(Payment $payment, array $extra = []): Payment
    {
        return self::transition($payment, 'succeeded', $extra);
    }

    public static function markFailed(Payment $payment, string $message, ?string $code = null): Payment
    {
        return self::transition($payment, 'failed', [
            'failure_message' => $message,
            'failure_code' => $code,
        ]);
    }

    public static function markProcessing(Payment $payment): Payment
    {
        return self::transition($payment, 'processing');
    }

    public static function markRequiresAction(Payment $payment, string $type, array $nextAction): Payment
    {
        return self::transition($payment, 'requires_action', [
            'next_action_type' => $type,
            'next_action' => $nextAction,
        ]);
    }

    public static function markCancelled(Payment $payment): Payment
    {
        return self::transition($payment, 'cancelled');
    }

    /**
     * All statuses a payment can be in.
     */
    public static function allStatuses(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
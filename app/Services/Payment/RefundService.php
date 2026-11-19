<?php

namespace App\Services\Payment;

use App\Models\Payment\AppliedFee;
use App\Models\Payment\Balance;
use App\Models\Payment\BalanceTransaction;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Mark a refund as succeeded and reverse the money.
     */
    public static function markSucceeded(Refund $refund): Refund
    {
        return DB::transaction(function () use ($refund) {
            $payment = $refund->payment;

            // Idempotency — if already succeeded, no-op
            if ($refund->status === 'succeeded') {
                return $refund;
            }

            // 1. Update the refund
            $refund->update([
                'status' => 'succeeded',
                'processed_at' => now(),
            ]);

            // 2. Update the payment
            $payment->increment('amount_refunded', $refund->amount);

            $totalRefunded = $payment->fresh()->amount_refunded;
            $newStatus = $totalRefunded >= $payment->amount_captured
                ? 'refunded'
                : 'partially_refunded';

            $payment->update(['status' => $newStatus]);

            // 3. Post the reversing ledger entries
            self::postRefundLedger($refund, $payment);

            // 4. Debit the merchant's balance
            self::debitMerchantBalance($refund, $payment);

            // 5. Audit
            \App\Models\Platform\AuditLog::record([
                'company_id' => $payment->company_id,
                'mode' => $payment->mode,
                'action' => 'refund.succeeded',
                'auditable_type' => Refund::class,
                'auditable_id' => $refund->id,
                'resource_public_id' => $refund->public_id,
                'new_values' => [
                    'amount' => $refund->amount,
                    'payment_id' => $payment->public_id,
                ],
                'actor_type' => $refund->initiated_by_id ? 'user' : 'system',
                'description' => "Refund {$refund->public_id} processed for {$payment->public_id}",
            ]);

            // 6. Dispatch
            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payment->company,
                type: 'refund.succeeded',
                data: [
                    'id' => $refund->public_id,
                    'object' => 'refund',
                    'status' => $refund->status,
                    'amount' => $refund->amount,
                    'currency' => $refund->currency,
                    'payment_id' => $payment->public_id,
                    'reason' => $refund->reason,
                    'is_partial' => (bool) $refund->is_partial,
                    'created' => $refund->created_at->toIso8601String(),
                    'processed_at' => $refund->processed_at?->toIso8601String(),
                ],
                resource: $refund,
                origin: 'system',
            );

            return $refund->fresh();
        });
    }

    protected static function postRefundLedger(Refund $refund, Payment $payment): void
    {
        $merchantPayable = LedgerService::merchantPayable(
            $payment->company_id, $payment->mode, $payment->currency
        );
        $providerReceivable = LedgerService::providerReceivable(
            $payment->payment_provider_id, $payment->mode, $payment->currency
        );

        // Reverse: debit merchant payable, credit provider receivable
        LedgerService::post(
            mode: $payment->mode,
            type: 'refund_processed',
            source: $refund,
            currency: $payment->currency,
            amount: $refund->amount,
            lines: [
                ['account' => $merchantPayable, 'direction' => 'debit', 'amount' => $refund->amount],
                ['account' => $providerReceivable, 'direction' => 'credit', 'amount' => $refund->amount],
            ],
            idempotencyKey: "refund_processed:{$refund->id}",
            companyId: $payment->company_id,
            description: "Refund {$refund->public_id} for payment {$payment->public_id}"
        );
    }

    protected static function debitMerchantBalance(Refund $refund, Payment $payment): void
    {
        $balance = Balance::where('company_id', $payment->company_id)
            ->where('mode', $payment->mode)
            ->where('currency', $payment->currency)
            ->lockForUpdate()
            ->first();

        if (!$balance) return;

        // Debit available (or pending if the original hasn't matured yet)
        $fromPending = $balance->pending_amount >= $refund->amount;

        if ($fromPending) {
            $balance->decrement('pending_amount', $refund->amount);
        } else {
            $balance->decrement('available_amount', $refund->amount);
        }

        $balance->update(['last_transaction_at' => now()]);

        BalanceTransaction::create([
            'company_id' => $payment->company_id,
            'balance_id' => $balance->id,
            'mode' => $payment->mode,
            'type' => 'refund',
            'source_type' => Refund::class,
            'source_id' => $refund->id,
            'currency' => $payment->currency,
            'gross_amount' => -$refund->amount,
            'fee_amount' => 0,
            'net_amount' => -$refund->amount,
            'available_balance_after' => $balance->fresh()->available_amount,
            'pending_balance_after' => $balance->fresh()->pending_amount,
            'status' => 'available',
            'description' => "Refund for {$payment->public_id}",
        ]);
    }

    /**
     * Record a refund attempt (pending). Called from API or admin UI.
     */
    public static function createForPayment(Payment $payment, int $amount, array $data = []): Refund
    {
        $refundable = $payment->amount_captured - $payment->amount_refunded;

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be positive.');
        }

        if ($amount > $refundable) {
            throw new \RuntimeException("Refund exceeds refundable amount ({$refundable}).");
        }

        $refund = Refund::create([
            'company_id' => $payment->company_id,
            'payment_id' => $payment->id,
            'customer_id' => $payment->customer_id,
            'mode' => $payment->mode,
            'currency' => $payment->currency,
            'amount' => $amount,
            'is_partial' => $amount < $payment->amount_captured,
            'reason' => $data['reason'] ?? 'requested_by_customer',
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'source' => $data['source'] ?? 'api',
            'payment_provider_id' => $payment->payment_provider_id,
            'initiated_by_id' => $data['initiated_by_id'] ?? auth()->id(),
            'idempotency_key' => $data['idempotency_key'] ?? null,
        ]);

        \App\Services\Webhook\EventDispatcher::dispatch(
            company: $payment->company,
            type: 'refund.created',
            data: [
                'id' => $refund->public_id,
                'object' => 'refund',
                'status' => $refund->status,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'payment_id' => $payment->public_id,
                'reason' => $refund->reason,
                'created' => $refund->created_at->toIso8601String(),
            ],
            resource: $refund,
            origin: 'system',
        );

        return $refund;

    }
}
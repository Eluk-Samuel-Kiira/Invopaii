<?php

namespace App\Services\Payment;

use App\Models\Payment\Balance;
use App\Models\Payment\BalanceTransaction;
use App\Models\Payment\Company\CompanyBankAccount;
use App\Services\Payment\LedgerService;
use App\Models\Payment\Payout;
use App\Models\Payment\PayoutItem;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Create a payout from all available balance transactions for a company.
     * Does NOT send to the bank — that's a separate step.
     */
    public static function createFromAvailableBalance(
        int $companyId,
        string $mode,
        string $currency,
        int $bankAccountId,
        string $type = 'manual',
        ?string $scheduledFor = null
    ): Payout {
        return DB::transaction(function () use ($companyId, $mode, $currency, $bankAccountId, $type, $scheduledFor) {
            $balance = Balance::where('company_id', $companyId)
                ->where('mode', $mode)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->available_amount <= 0) {
                throw new \RuntimeException('No available balance to pay out.');
            }

            // Collect eligible transactions (available, not yet paid out)
            $transactions = BalanceTransaction::where('balance_id', $balance->id)
                ->where('status', 'available')
                ->whereNull('payout_id')
                ->where(function ($q) {
                    $q->whereNull('available_on')->orWhere('available_on', '<=', now());
                })
                ->get();

            if ($transactions->isEmpty()) {
                throw new \RuntimeException('No balance transactions ready to pay out.');
            }

            $gross = (int) $transactions->sum('net_amount');
            $payoutFee = 0; // Fee schedule could compute this
            $adjustment = 0;
            $net = $gross + $adjustment - $payoutFee;

            if ($net <= 0) {
                throw new \RuntimeException('Payout amount would be zero or negative.');
            }

            // Create the payout
            $payout = Payout::create([
                'company_id' => $companyId,
                'company_bank_account_id' => $bankAccountId,
                'mode' => $mode,
                'currency' => $currency,
                'gross_amount' => $gross,
                'fee_amount' => $payoutFee,
                'adjustment_amount' => $adjustment,
                'amount' => $net,
                'type' => $type,
                'method' => 'bank_transfer',
                'status' => 'pending',
                'period_start' => $transactions->min('available_on'),
                'period_end' => $transactions->max('available_on'),
                'transaction_count' => $transactions->count(),
                'scheduled_for' => $scheduledFor,
            ]);

            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payout->company,
                type: 'payout.created',
                data: [
                    'id' => $payout->public_id,
                    'object' => 'payout',
                    'status' => $payout->status,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'method' => $payout->method,
                    'expected_arrival_date' => $payout->expected_arrival_date?->toDateString(),
                    'created' => $payout->created_at->toIso8601String(),
                ],
                resource: $payout,
                origin: 'system',
            );

            // Attach items and mark transactions as committed to this payout
            foreach ($transactions as $txn) {
                PayoutItem::create([
                    'payout_id' => $payout->id,
                    'balance_transaction_id' => $txn->id,
                    'type' => $txn->type,
                    'amount' => $txn->net_amount,
                    'currency' => $txn->currency,
                ]);

                $txn->update([
                    'payout_id' => $payout->id,
                    'status' => 'pending',   // committed, not yet paid
                ]);
            }

            // Update balance
            $balance->decrement('available_amount', $gross);
            $balance->increment('payout_in_transit', $net);
            $balance->update(['last_transaction_at' => now()]);

            return $payout->fresh(['items']);
        });
    }

    /**
     * Mark a payout as approved (moves it toward processing).
     */
    public static function approve(Payout $payout, int $userId): Payout
    {
        if ($payout->status !== 'pending') {
            throw new \RuntimeException("Cannot approve a payout in status [{$payout->status}].");
        }

        $payout->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => $userId,
        ]);

        return $payout->fresh();
    }

    /**
     * Mark a payout as paid (bank has confirmed).
     */
    public static function markPaid(Payout $payout): Payout
    {
        if (!in_array($payout->status, ['approved', 'processing', 'in_transit'])) {
            throw new \RuntimeException("Cannot mark paid a payout in status [{$payout->status}].");
        }

        return DB::transaction(function () use ($payout) {
            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Release payout_in_transit
            $balance = Balance::where('company_id', $payout->company_id)
                ->where('mode', $payout->mode)
                ->where('currency', $payout->currency)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                $balance->decrement('payout_in_transit', $payout->amount);
                $balance->update(['last_transaction_at' => now()]);
            }

            // Mark attached transactions as paid
            BalanceTransaction::where('payout_id', $payout->id)
                ->update(['status' => 'paid']);

            // Ledger: debit merchant_payable, credit provider_settled
            $merchantPayable = LedgerService::merchantPayable($payout->company_id, $payout->mode, $payout->currency);

            $settledAccount = \App\Models\Payment\LedgerAccount::findOrCreateByCode(
                "provider_settled:{$payout->currency}:{$payout->mode}",
                [
                    'name' => "Provider Settled ({$payout->currency} {$payout->mode})",
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'category' => 'provider_receivable',
                    'currency' => $payout->currency,
                    'mode' => $payout->mode,
                ]
            );

            LedgerService::post(
                mode: $payout->mode,
                type: 'payout_paid',
                source: $payout,
                currency: $payout->currency,
                amount: $payout->amount,
                lines: [
                    ['account' => $merchantPayable, 'direction' => 'debit',  'amount' => $payout->amount],
                    ['account' => $settledAccount,  'direction' => 'credit', 'amount' => $payout->amount],
                ],
                idempotencyKey: "payout_paid:{$payout->id}",
                companyId: $payout->company_id,
                description: "Payout #{$payout->public_id} settled",
            );

            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payout->company,
                type: 'payout.paid',
                data: [
                    'id' => $payout->public_id,
                    'object' => 'payout',
                    'status' => $payout->status,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'method' => $payout->method,
                    'paid_at' => $payout->paid_at?->toIso8601String(),
                    'created' => $payout->created_at->toIso8601String(),
                ],
                resource: $payout,
                origin: 'system',
            );

            return $payout->fresh();
        });
    }

    /**
     * Mark a payout as failed. Releases the balance back to available.
     */
    public static function markFailed(Payout $payout, string $message, ?string $code = null): Payout
    {
        if ($payout->is_completed) {
            throw new \RuntimeException("Cannot fail a payout in status [{$payout->status}].");
        }

        return DB::transaction(function () use ($payout, $message, $code) {
            $payout->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_code' => $code,
                'failure_message' => $message,
            ]);

            // Release in-transit back to available
            $balance = Balance::where('company_id', $payout->company_id)
                ->where('mode', $payout->mode)
                ->where('currency', $payout->currency)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                $balance->decrement('payout_in_transit', $payout->amount);
                $balance->increment('available_amount', $payout->gross_amount);
                $balance->update(['last_transaction_at' => now()]);
            }

            // Unblock the transactions
            BalanceTransaction::where('payout_id', $payout->id)
                ->update(['payout_id' => null, 'status' => 'available']);

            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payout->company,
                type: 'payout.failed',
                data: [
                    'id' => $payout->public_id,
                    'object' => 'payout',
                    'status' => $payout->status,
                    'amount' => $payout->amount,
                    'currency' => $payout->currency,
                    'failure_code' => $payout->failure_code,
                    'failure_message' => $payout->failure_message,
                    'created' => $payout->created_at->toIso8601String(),
                ],
                resource: $payout,
                origin: 'system',
            );

            return $payout->fresh();
        });
    }
}
<?php

namespace App\Services\Payment;

use App\Models\Payment\Balance;
use App\Models\Payment\BalanceTransaction;
use App\Models\Payment\LedgerAccount;
use App\Models\Payment\LedgerEntry;
use App\Models\Payment\LedgerTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a double-entry transaction.
     *
     * @param string $mode
     * @param string $type           e.g. 'payment_captured'
     * @param Model|null $source     the payment/refund/dispute/payout this came from
     * @param string $currency
     * @param int $amount            absolute value of the movement
     * @param array $lines           [['account' => LedgerAccount, 'direction' => 'debit'|'credit', 'amount' => int], ...]
     * @param string|null $idempotencyKey  if provided, second call with same key is a no-op
     */
    public static function post(
        string $mode,
        string $type,
        ?Model $source,
        string $currency,
        int $amount,
        array $lines,
        ?string $idempotencyKey = null,
        ?int $companyId = null,
        ?string $description = null
    ): LedgerTransaction {
        // Idempotency check
        if ($idempotencyKey) {
            $existing = LedgerTransaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        // Validate balance before inserting anything
        self::assertLinesBalance($lines);

        return DB::transaction(function () use (
            $mode, $type, $source, $currency, $amount, $lines,
            $idempotencyKey, $companyId, $description
        ) {
            $transaction = LedgerTransaction::create([
                'mode' => $mode,
                'type' => $type,
                'source_type' => $source ? get_class($source) : null,
                'source_id' => $source?->id,
                'company_id' => $companyId ?? $source?->company_id,
                'currency' => $currency,
                'amount' => $amount,
                'description' => $description,
                'idempotency_key' => $idempotencyKey,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                /** @var LedgerAccount $account */
                $account = $line['account'];
                $direction = $line['direction'];
                $lineAmount = (int) $line['amount'];

                // Lock the account row for update
                $lockedAccount = LedgerAccount::where('id', $account->id)
                    ->lockForUpdate()
                    ->first();

                // Compute new balance based on normal_balance
                $delta = self::signedDelta($direction, $lockedAccount->normal_balance, $lineAmount);
                $newBalance = $lockedAccount->balance + $delta;

                LedgerEntry::create([
                    'ledger_transaction_id' => $transaction->id,
                    'ledger_account_id' => $lockedAccount->id,
                    'direction' => $direction,
                    'amount' => $lineAmount,
                    'currency' => $currency,
                    'balance_after' => $newBalance,
                    'posted_at' => now(),
                ]);

                $lockedAccount->update(['balance' => $newBalance]);
            }

            return $transaction;
        });
    }

    /**
     * Every line must balance — sum of debit amounts == sum of credit amounts.
     */
    protected static function assertLinesBalance(array $lines): void
    {
        $debits = 0;
        $credits = 0;

        foreach ($lines as $line) {
            if ($line['direction'] === 'debit') {
                $debits += (int) $line['amount'];
            } else {
                $credits += (int) $line['amount'];
            }
        }

        if ($debits !== $credits) {
            throw new \RuntimeException(
                "Ledger lines do not balance: debits={$debits} credits={$credits}"
            );
        }
    }

    /**
     * Compute the signed delta to add to the account balance.
     *
     * If the entry direction matches the account's normal_balance, the balance grows.
     * Otherwise, it shrinks.
     */
    protected static function signedDelta(string $entryDirection, string $normalBalance, int $amount): int
    {
        return $entryDirection === $normalBalance ? $amount : -$amount;
    }

    /* ═══════════════════════════════════════════════════════
       HIGH-LEVEL HELPERS
       ═══════════════════════════════════════════════════════ */

    /**
     * Account lookup/create helpers for common account types.
     */
    public static function merchantPayable(int $companyId, string $mode, string $currency): LedgerAccount
    {
        $code = "merchant_payable:{$companyId}:{$currency}:{$mode}";
        return LedgerAccount::findOrCreateByCode($code, [
            'name' => "Merchant Payable {$companyId} ({$currency} {$mode})",
            'type' => 'liability',
            'normal_balance' => 'credit',
            'category' => 'merchant_payable',
            'company_id' => $companyId,
            'currency' => $currency,
            'mode' => $mode,
        ]);
    }

    public static function platformRevenue(string $mode, string $currency): LedgerAccount
    {
        $code = "platform_revenue:{$currency}:{$mode}";
        return LedgerAccount::findOrCreateByCode($code, [
            'name' => "Platform Revenue ({$currency} {$mode})",
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'category' => 'platform_revenue',
            'currency' => $currency,
            'mode' => $mode,
        ]);
    }

    public static function providerReceivable(int $providerId, string $mode, string $currency): LedgerAccount
    {
        $code = "provider_receivable:{$providerId}:{$currency}:{$mode}";
        return LedgerAccount::findOrCreateByCode($code, [
            'name' => "Provider Receivable {$providerId} ({$currency} {$mode})",
            'type' => 'asset',
            'normal_balance' => 'debit',
            'category' => 'provider_receivable',
            'currency' => $currency,
            'mode' => $mode,
        ]);
    }

    public static function fxSuspense(string $mode, string $currency): LedgerAccount
    {
        $code = "fx_suspense:{$currency}:{$mode}";
        return LedgerAccount::findOrCreateByCode($code, [
            'name' => "FX Suspense ({$currency} {$mode})",
            'type' => 'asset',
            'normal_balance' => 'debit',
            'category' => 'fx_suspense',
            'currency' => $currency,
            'mode' => $mode,
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       BALANCE HELPERS
       ═══════════════════════════════════════════════════════ */

    /**
     * Credit a merchant's available balance and record a balance transaction.
     * Assumes the corresponding ledger entries have been posted.
     */
    public static function creditMerchantAvailable(
        int $companyId,
        string $mode,
        string $currency,
        int $amount,
        Model $source,
        string $type,
        ?string $description = null
    ): BalanceTransaction {
        return DB::transaction(function () use ($companyId, $mode, $currency, $amount, $source, $type, $description) {
            $balance = Balance::where('company_id', $companyId)
                ->where('mode', $mode)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            $balance->increment('available_amount', $amount);
            $balance->increment('lifetime_volume', $amount);
            $balance->update(['last_transaction_at' => now()]);

            return BalanceTransaction::create([
                'company_id' => $companyId,
                'balance_id' => $balance->id,
                'mode' => $mode,
                'type' => $type,
                'source_type' => get_class($source),
                'source_id' => $source->id,
                'currency' => $currency,
                'gross_amount' => $amount,
                'fee_amount' => 0,
                'net_amount' => $amount,
                'available_balance_after' => $balance->fresh()->available_amount,
                'status' => 'available',
                'description' => $description,
            ]);
        });
    }

    /**
     * Debit a merchant's available balance.
     */
    public static function debitMerchantAvailable(
        int $companyId,
        string $mode,
        string $currency,
        int $amount,
        Model $source,
        string $type,
        ?string $description = null
    ): BalanceTransaction {
        return DB::transaction(function () use ($companyId, $mode, $currency, $amount, $source, $type, $description) {
            $balance = Balance::where('company_id', $companyId)
                ->where('mode', $mode)
                ->where('currency', $currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->available_amount < $amount) {
                throw new \RuntimeException(
                    "Insufficient balance: have {$balance->available_amount}, need {$amount}"
                );
            }

            $balance->decrement('available_amount', $amount);
            $balance->update(['last_transaction_at' => now()]);

            return BalanceTransaction::create([
                'company_id' => $companyId,
                'balance_id' => $balance->id,
                'mode' => $mode,
                'type' => $type,
                'source_type' => get_class($source),
                'source_id' => $source->id,
                'currency' => $currency,
                'gross_amount' => -$amount,
                'fee_amount' => 0,
                'net_amount' => -$amount,
                'available_balance_after' => $balance->fresh()->available_amount,
                'status' => 'available',
                'description' => $description,
            ]);
        });
    }
}
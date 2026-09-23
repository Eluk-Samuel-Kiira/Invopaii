<?php

namespace App\Services\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\ProviderCredential;
use App\Services\Payment\Providers\ProviderInterface;
use App\Services\Payment\Providers\ProviderResult;
use Illuminate\Support\Facades\Log;

class PaymentProcessor
{
    /**
     * Run the payment through the provider. Creates an attempt row.
     * Handles both success and failure. Does NOT throw on provider failure —
     * the payment is marked failed and returned.
     */
    public static function process(Payment $payment, bool $isRetry = false, bool $isFallback = false): Payment
    {
        // Reload fresh
        $payment = $payment->fresh(['company', 'customer', 'provider', 'paymentMethod']);

        if (in_array($payment->status, ['succeeded', 'refunded', 'partially_refunded'])) {
            return $payment;
        }

        // 1. Resolve provider
        $provider = $payment->provider;

        if (!$provider || !$provider->is_active) {
            [$provider] = ProviderRouter::routePayment($payment);

            if (!$provider) {
                return PaymentStateMachine::markFailed($payment, 'No provider available', 'no_provider');
            }
            $payment->update(['payment_provider_id' => $provider->id]);
        }

        // 2. Resolve service class
        $serviceClass = ProviderRouter::resolveProviderClass($provider->code);

        if (!$serviceClass || !class_exists($serviceClass)) {
            return PaymentStateMachine::markFailed($payment, "Provider implementation missing for [{$provider->code}]", 'provider_not_implemented');
        }

        /** @var ProviderInterface $service */
        $service = new $serviceClass();

        // 3. Load credentials
        $credential = $provider->getCredentialFor($payment->company_id, $payment->mode)
            ?? $provider->getCredentialFor(null, $payment->mode);

        if (!$credential) {
            return PaymentStateMachine::markFailed($payment, 'No credentials configured for this provider', 'no_credentials');
        }

        // 4. Create the attempt row
        $attemptNumber = ($payment->attempts()->max('attempt_number') ?? 0) + 1;

        $attempt = $payment->attempts()->create([
            'company_id' => $payment->company_id,
            'payment_provider_id' => $provider->id,
            'mode' => $payment->mode,
            'attempt_number' => $attemptNumber,
            'operation' => 'charge',
            'status' => 'initiated',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'is_retry' => $isRetry,
            'is_fallback' => $isFallback,
            'started_at' => now(),
        ]);

        $payment->update([
            'status' => 'processing',
            'attempt_count' => $attemptNumber,
        ]);

        // 5. Call the provider
        $started = microtime(true);
        $result = null;
        $exception = null;

        try {
            $result = $service->charge($attempt, $credential);
        } catch (\Throwable $e) {
            $exception = $e;
            Log::error("Provider charge exception [{$provider->code}]", [
                'payment' => $payment->public_id,
                'attempt' => $attempt->uuid,
                'error' => $e->getMessage(),
            ]);
        }

        $duration = (int) round((microtime(true) - $started) * 1000);

        // 6. Handle exception — mark attempt failed, payment failed
        if ($exception) {
            $attempt->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
                'duration_ms' => $duration,
                'completed_at' => now(),
            ]);

            return PaymentStateMachine::markFailed(
                $payment,
                'Provider error: ' . $exception->getMessage(),
                'provider_exception'
            );
        }

        // 7. Handle result
        return self::handleResult($payment, $attempt, $result, $duration, $provider);
    }

    /**
     * Retry a failed payment. Creates a new attempt against the same provider
     * (or the fallback if configured).
     */
    public static function retry(Payment $payment, bool $useFallback = false): Payment
    {
        $payment = $payment->fresh();

        if (!in_array($payment->status, ['failed', 'requires_payment_method'])) {
            throw new \RuntimeException("Cannot retry a payment in status [{$payment->status}].");
        }

        if ($useFallback) {
            [$provider, $fallback] = ProviderRouter::routePayment($payment);

            if ($fallback) {
                $payment->update(['payment_provider_id' => $fallback->id]);
                return self::process($payment, isRetry: true, isFallback: true);
            }
        }

        return self::process($payment, isRetry: true);
    }

    /* ---------- Internal ---------- */

    protected static function handleResult(
        Payment $payment,
        PaymentAttempt $attempt,
        ProviderResult $result,
        int $duration,
        $provider
    ): Payment {
        // Update attempt
        $attempt->update([
            'status' => $result->success ? 'succeeded' : 'failed',
            'provider_reference' => $result->providerReference,
            'provider_status' => $result->providerStatus,
            'provider_code' => $result->providerCode,
            'provider_message' => $result->providerMessage,
            'response_payload' => $result->responsePayload,
            'duration_ms' => $duration,
            'failure_reason' => $result->failureReason,
            'completed_at' => now(),
        ]);

        // Update provider health metrics
        $provider->update(['health_checked_at' => now()]);

        // Route based on result status
        return match ($result->status) {
            'succeeded' => self::handleSuccess($payment, $result),
            'processing', 'pending' => self::handlePending($payment, $result),
            'requires_action' => self::handleRequiresAction($payment, $result),
            default => self::handleFailure($payment, $result),
        };
    }

    protected static function handleSuccess(Payment $payment, ProviderResult $result): Payment
    {
        return \DB::transaction(function () use ($payment, $result) {
            // 1. Transition to succeeded
            $payment = PaymentStateMachine::transition($payment, 'succeeded', [
                'provider_reference' => $result->providerReference,
                'provider_status' => $result->providerStatus,
                'provider_authorization_code' => $result->authorizationCode,
                'acquirer_reference' => $result->acquirerReference,
                'amount_captured' => $payment->amount,
                'captured_at' => now(),
            ]);

            // 2. Compute the fee
            $appliedFee = FeeService::applyProcessingFee($payment);
            $feeTotal = $appliedFee?->total_amount ?? 0;

            // 3. Post the ledger entries
            self::postPaymentLedger($payment, $feeTotal);

            // 4. Update the merchant's balance
            self::creditMerchantBalance($payment, $feeTotal);

            // 5. Audit + notify
            \App\Models\Platform\AuditLog::record([
                'company_id' => $payment->company_id,
                'mode' => $payment->mode,
                'action' => 'payment.succeeded',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'resource_public_id' => $payment->public_id,
                'new_values' => [
                    'amount' => $payment->amount,
                    'fee' => $feeTotal,
                    'net' => $payment->amount - $feeTotal,
                    'provider' => $payment->provider?->code,
                    'provider_reference' => $payment->provider_reference,
                ],
                'actor_type' => 'system',
                'description' => "Payment {$payment->public_id} captured via {$payment->provider?->name}",
            ]);

            // 6. Notify the merchant
            self::notifyPaymentSucceeded($payment, $feeTotal);

            // Fire the webhook event
            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payment->company,
                type: 'payment.succeeded',
                data: self::paymentEventPayload($payment, $feeTotal),
                resource: $payment,
                origin: 'system',
                mode: $payment->mode,
            );

            return $payment->fresh();
        });
    }

    protected static function paymentEventPayload(Payment $payment, int $feeTotal): array
    {
        return [
            'id' => $payment->public_id,
            'object' => 'payment',
            'status' => $payment->status,
            'amount' => $payment->amount,
            'amount_captured' => $payment->amount_captured,
            'amount_refunded' => $payment->amount_refunded,
            'fee_amount' => $payment->fee_amount,
            'net_amount' => $payment->net_amount,
            'currency' => $payment->currency,
            'payment_method_type' => $payment->payment_method_type,
            'customer_id' => $payment->customer?->public_id,
            'customer_email' => $payment->receipt_email,
            'reference' => $payment->reference,
            'description' => $payment->description,
            'metadata' => $payment->metadata ?? new \stdClass(),
            'provider' => $payment->provider?->code,
            'provider_reference' => $payment->provider_reference,
            'created' => $payment->created_at->toIso8601String(),
            'succeeded_at' => $payment->succeeded_at?->toIso8601String(),
        ];
    }

    /**
     * Post the double-entry ledger for a successful payment.
     *
     *   Debit:  provider_receivable   (we now hold a claim against the provider)
     *   Credit: merchant_payable      (we owe the merchant — amount minus fee)
     *   Credit: platform_revenue      (our fee + tax on the fee)
     */
    protected static function postPaymentLedger(Payment $payment, int $feeTotal): void
    {
        $providerReceivable = LedgerService::providerReceivable(
            $payment->payment_provider_id,
            $payment->mode,
            $payment->currency
        );

        $merchantPayable = LedgerService::merchantPayable(
            $payment->company_id,
            $payment->mode,
            $payment->currency
        );

        $platformRevenue = LedgerService::platformRevenue(
            $payment->mode,
            $payment->currency
        );

        $netToMerchant = $payment->amount - $feeTotal;

        $lines = [
            [
                'account' => $providerReceivable,
                'direction' => 'debit',
                'amount' => $payment->amount,
            ],
            [
                'account' => $merchantPayable,
                'direction' => 'credit',
                'amount' => $netToMerchant,
            ],
        ];

        // If there's a fee, credit platform revenue for the difference
        if ($feeTotal > 0) {
            $lines[] = [
                'account' => $platformRevenue,
                'direction' => 'credit',
                'amount' => $feeTotal,
            ];
        }

        LedgerService::post(
            mode: $payment->mode,
            type: 'payment_captured',
            source: $payment,
            currency: $payment->currency,
            amount: $payment->amount,
            lines: $lines,
            idempotencyKey: "payment_captured:{$payment->id}",
            companyId: $payment->company_id,
            description: "Payment {$payment->public_id} captured"
        );
    }

    /**
     * Credit the merchant's available balance for the net amount.
     */
    protected static function creditMerchantBalance(Payment $payment, int $feeTotal): void
    {
        $net = $payment->amount - $feeTotal;

        // Ensure the balance row exists
        $balance = \App\Models\Payment\Balance::firstOrCreate(
            [
                'company_id' => $payment->company_id,
                'mode' => $payment->mode,
                'currency' => $payment->currency,
            ],
            [
                'available_amount' => 0,
                'pending_amount' => 0,
                'reserved_amount' => 0,
                'payout_in_transit' => 0,
                'lifetime_volume' => 0,
                'lifetime_fees' => 0,
            ]
        );

        // Respect the payout delay — if the company has one, the money sits in pending first
        $delayDays = $payment->company?->payout_delay_days ?? 0;
        $isPending = $delayDays > 0;

        \DB::transaction(function () use ($balance, $payment, $net, $feeTotal, $isPending, $delayDays) {
            // Lock the balance row
            $balance = \App\Models\Payment\Balance::where('id', $balance->id)
                ->lockForUpdate()
                ->first();

            if ($isPending) {
                $balance->increment('pending_amount', $net);
            } else {
                $balance->increment('available_amount', $net);
            }

            $balance->increment('lifetime_volume', $payment->amount);
            $balance->increment('lifetime_fees', $feeTotal);
            $balance->update(['last_transaction_at' => now()]);

            // Write the balance transaction
            \App\Models\Payment\BalanceTransaction::create([
                'company_id' => $payment->company_id,
                'balance_id' => $balance->id,
                'mode' => $payment->mode,
                'type' => 'charge',
                'source_type' => \App\Models\Payment\Payment::class,
                'source_id' => $payment->id,
                'currency' => $payment->currency,
                'gross_amount' => $payment->amount,
                'fee_amount' => $feeTotal,
                'net_amount' => $net,
                'available_balance_after' => $balance->fresh()->available_amount,
                'pending_balance_after' => $balance->fresh()->pending_amount,
                'status' => $isPending ? 'pending' : 'available',
                'available_on' => $isPending ? now()->addDays($delayDays)->toDateString() : now()->toDateString(),
                'description' => "Payment {$payment->public_id}",
                'reference' => $payment->public_id,
            ]);

            // Write the fee as its own balance transaction
            if ($feeTotal > 0) {
                \App\Models\Payment\BalanceTransaction::create([
                    'company_id' => $payment->company_id,
                    'balance_id' => $balance->id,
                    'mode' => $payment->mode,
                    'type' => 'fee',
                    'source_type' => \App\Models\Payment\Payment::class,
                    'source_id' => $payment->id,
                    'currency' => $payment->currency,
                    'gross_amount' => -$feeTotal,
                    'fee_amount' => 0,
                    'net_amount' => -$feeTotal,
                    'available_balance_after' => $balance->fresh()->available_amount,
                    'status' => 'available',
                    'description' => "Processing fee for {$payment->public_id}",
                ]);
            }
        });
    }

    /**
     * Fire a notification for the merchant's team.
     */
    protected static function notifyPaymentSucceeded(Payment $payment, int $feeTotal): void
    {
        // Company-scoped notification for merchant admins
        // (Uses Laravel's built-in notifications table — the merchant sees it in-app.)

        $recipients = \App\Models\User::role(['merchant_admin', 'merchant_operator'])
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $payment->company_id))
            ->get();

        foreach ($recipients as $user) {
            $user->notify(new \App\Notifications\PaymentSucceededNotification($payment, $feeTotal));
        }
    }

    protected static function handlePending(Payment $payment, ProviderResult $result): Payment
    {
        // Already in processing — just store the reference
        $payment->update([
            'provider_reference' => $result->providerReference,
            'provider_status' => $result->providerStatus,
        ]);

        return $payment->fresh();
    }

    protected static function handleRequiresAction(Payment $payment, ProviderResult $result): Payment
    {
        $payment = PaymentStateMachine::transition($payment, 'requires_action', [
            'provider_reference' => $result->providerReference,
            'next_action_type' => $result->nextActionType,
            'next_action' => $result->nextAction,
        ]);

        \App\Services\Webhook\EventDispatcher::dispatch(
            company: $payment->company,
            type: 'payment.requires_action',
            data: array_merge(self::paymentEventPayload($payment, 0), [
                'next_action_type' => $result->nextActionType,
                'next_action' => $result->nextAction,
            ]),
            resource: $payment,
            origin: 'system',
        );

        return $payment->fresh();
    }

    protected static function handleFailure(Payment $payment, ProviderResult $result): Payment
    {
        return \DB::transaction(function () use ($payment, $result) {
            $payment = PaymentStateMachine::markFailed(
                $payment,
                $result->failureReason ?? 'Payment failed',
                $result->failureCode
            );

            \App\Models\Platform\AuditLog::record([
                'company_id' => $payment->company_id,
                'mode' => $payment->mode,
                'action' => 'payment.failed',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'resource_public_id' => $payment->public_id,
                'new_values' => [
                    'reason' => $result->failureReason,
                    'code' => $result->failureCode,
                ],
                'actor_type' => 'system',
                'description' => "Payment {$payment->public_id} failed",
            ]);

            \App\Services\Webhook\EventDispatcher::dispatch(
                company: $payment->company,
                type: 'payment.failed',
                data: array_merge(self::paymentEventPayload($payment, 0), [
                    'failure_code' => $result->failureCode,
                    'failure_message' => $result->failureReason,
                ]),
                resource: $payment,
                origin: 'system',
            );

            return $payment->fresh();
        });
    }

    
}
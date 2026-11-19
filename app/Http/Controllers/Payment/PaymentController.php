<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentAttempt;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\ProviderWebhookEvent;
use App\Services\Payment\PaymentProcessor;
use App\Services\Payment\PaymentStateMachine;
use App\Services\Payment\WebhookReceiver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       PAYMENTS LIST
       ═══════════════════════════════════════════════════════ */

    public function index()
    {
        return view('payments.index');
    }

    public function getPayments(Request $request)
    {
        $query = Payment::query()
            ->with([
                'company:id,name,public_id',
                'customer:id,public_id,name,email',
                'provider:id,code,name,type',
            ])
            ->search($request->get('search'));

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->pending();
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('payment_method_type')) {
            $query->where('payment_method_type', $request->payment_method_type);
        }
        if ($request->filled('payment_provider_id')) {
            $query->where('payment_provider_id', $request->payment_provider_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $payments = $query->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'current_page' => $payments->currentPage(),
            'data' => collect($payments->items())->map(fn ($p) => $this->format($p))->toArray(),
            'from' => $payments->firstItem(),
            'last_page' => $payments->lastPage(),
            'next_page_url' => $payments->nextPageUrl(),
            'prev_page_url' => $payments->previousPageUrl(),
            'to' => $payments->lastItem(),
            'total' => $payments->total(),
        ]);
    }

    public function getStats(Request $request)
    {
        $base = Payment::query();
        if ($request->filled('company_id')) $base->where('company_id', $request->company_id);
        if ($request->filled('mode')) $base->where('mode', $request->mode);

        $succeededAmount = (clone $base)->where('status', 'succeeded')->sum('amount_captured');
        $succeededCount = (clone $base)->where('status', 'succeeded')->count();
        $succeededCurrency = (clone $base)->where('status', 'succeeded')->value('currency') ?? 'USD';

        return response()->json([
            'total'            => (clone $base)->count(),
            'succeeded'        => $succeededCount,
            'failed'           => (clone $base)->where('status', 'failed')->count(),
            'pending'          => (clone $base)->pending()->count(),
            'refunded'         => (clone $base)->whereIn('status', ['refunded', 'partially_refunded'])->count(),
            'volume_captured'  => (int) $succeededAmount,
            'volume_currency'  => $succeededCurrency,
            'disputed'         => (clone $base)->where('is_disputed', true)->count(),
        ]);
    }

    public function getPayment($id)
    {
        try {
            $payment = Payment::with([
                'company:id,name,public_id,default_currency',
                'customer:id,public_id,name,email,phone',
                'provider:id,code,name,type',
                'paymentMethod',
                'attempts.provider:id,code,name',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($payment), [
                    'amount_refunded' => $payment->amount_refunded,
                    'amount_disputed' => $payment->amount_disputed,
                    'fee_amount' => $payment->fee_amount,
                    'net_amount' => $payment->net_amount,
                    'settlement_currency' => $payment->settlement_currency,
                    'settlement_amount' => $payment->settlement_amount,
                    'statement_descriptor' => $payment->statement_descriptor,
                    'description' => $payment->description,
                    'receipt_email' => $payment->receipt_email,
                    'acquirer_reference' => $payment->acquirer_reference,
                    'network_transaction_id' => $payment->network_transaction_id,
                    'next_action_type' => $payment->next_action_type,
                    'next_action' => $payment->next_action,
                    'failure_code' => $payment->failure_code,
                    'failure_message' => $payment->failure_message,
                    'risk_score' => $payment->risk_score,
                    'risk_level' => $payment->risk_level,
                    'is_disputed' => (bool) $payment->is_disputed,
                    'is_refundable' => $payment->is_refundable,
                    'refundable_amount' => $payment->refundable_amount,
                    'ip_address' => $payment->ip_address,
                    'customer_country' => $payment->customer_country,
                    'metadata' => $payment->metadata,
                    'attempts' => $payment->attempts->map(fn ($a) => $this->formatAttempt($a))->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }
    }

    public function getAttempts($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $attempts = $payment->attempts()->with('provider:id,code,name')->get();

            return response()->json([
                'success' => true,
                'data' => $attempts->map(fn ($a) => $this->formatAttempt($a))->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }
    }

    public function getFormOptions()
    {
        return response()->json([
            'companies' => Company::orderBy('name')->get(['id', 'name', 'public_id'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => "{$c->name} ({$c->public_id})"]),
            'providers' => PaymentProvider::orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => "{$p->name} ({$p->code})"]),
            'statuses' => [
                ['value' => 'requires_payment_method', 'label' => 'Requires Payment Method'],
                ['value' => 'requires_confirmation',   'label' => 'Requires Confirmation'],
                ['value' => 'requires_action',         'label' => 'Requires Action'],
                ['value' => 'processing',              'label' => 'Processing'],
                ['value' => 'requires_capture',        'label' => 'Requires Capture'],
                ['value' => 'succeeded',               'label' => 'Succeeded'],
                ['value' => 'partially_refunded',      'label' => 'Partially Refunded'],
                ['value' => 'refunded',                'label' => 'Refunded'],
                ['value' => 'failed',                  'label' => 'Failed'],
                ['value' => 'cancelled',               'label' => 'Cancelled'],
                ['value' => 'expired',                 'label' => 'Expired'],
                ['value' => 'reversed',                'label' => 'Reversed'],
            ],
            'methods' => [
                ['value' => 'card',          'label' => 'Card'],
                ['value' => 'mobile_money',  'label' => 'Mobile Money'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'ussd',          'label' => 'USSD'],
                ['value' => 'wallet',        'label' => 'Wallet'],
            ],
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       ACTIONS
       ═══════════════════════════════════════════════════════ */

    public function retryPayment(Request $request, $id)
    {
        $request->validate([
            'use_fallback' => ['nullable', 'boolean'],
        ]);

        try {
            $payment = Payment::findOrFail($id);

            if (!in_array($payment->status, ['failed', 'requires_payment_method'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot retry a payment in status [{$payment->status}].",
                ], 422);
            }

            $result = PaymentProcessor::retry($payment, $request->boolean('use_fallback'));

            return response()->json([
                'success' => true,
                'message' => "Retry completed with status: {$result->status}.",
                'data' => $this->format($result->fresh(['provider'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cancelPayment($id)
    {
        try {
            $payment = Payment::findOrFail($id);

            if (!in_array($payment->status, ['requires_payment_method', 'requires_confirmation', 'requires_action', 'processing'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot cancel a payment in status [{$payment->status}].",
                ], 422);
            }

            PaymentStateMachine::markCancelled($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled.',
                'data' => $this->format($payment->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       WEBHOOK EVENTS
       ═══════════════════════════════════════════════════════ */

    public function webhookEventsIndex()
    {
        return view('payments.webhook-events');
    }

    public function getWebhookEvents(Request $request)
    {
        $query = ProviderWebhookEvent::query()
            ->with('provider:id,code,name');

        if ($request->filled('provider_id')) {
            $query->where('payment_provider_id', $request->provider_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', 'like', "%{$request->event_type}%");
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('provider_event_id', 'like', "%{$s}%")
                  ->orWhere('provider_reference', 'like', "%{$s}%")
                  ->orWhere('event_type', 'like', "%{$s}%");
            });
        }

        $events = $query->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 25));

        return response()->json([
            'current_page' => $events->currentPage(),
            'data' => collect($events->items())->map(fn ($e) => $this->formatWebhookEvent($e))->toArray(),
            'from' => $events->firstItem(),
            'last_page' => $events->lastPage(),
            'next_page_url' => $events->nextPageUrl(),
            'prev_page_url' => $events->previousPageUrl(),
            'to' => $events->lastItem(),
            'total' => $events->total(),
        ]);
    }

    public function getWebhookEvent($id)
    {
        try {
            $event = ProviderWebhookEvent::with('provider:id,code,name')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->formatWebhookEvent($event), [
                    'headers' => $event->headers,
                    'payload' => $event->payload,
                    'processing_error' => $event->processing_error,
                    'process_attempts' => $event->process_attempts,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Event not found'], 404);
        }
    }

    public function reprocessWebhookEvent($id)
    {
        try {
            $event = ProviderWebhookEvent::findOrFail($id);

            WebhookReceiver::process($event);

            return response()->json([
                'success' => true,
                'message' => 'Event reprocessed.',
                'data' => $this->formatWebhookEvent($event->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to reprocess'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       FORMATTERS
       ═══════════════════════════════════════════════════════ */

    protected function format(Payment $p): array
    {
        return [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'public_id' => $p->public_id,
            'company_id' => $p->company_id,
            'company' => $p->company ? [
                'id' => $p->company->id,
                'name' => $p->company->name,
                'public_id' => $p->company->public_id,
            ] : null,
            'mode' => $p->mode,
            'mode_badge' => $p->mode_badge,
            'source' => $p->source,
            'currency' => $p->currency,
            'amount' => $p->amount,
            'amount_captured' => $p->amount_captured,
            'amount_display' => $this->money($p->amount, $p->currency),
            'amount_captured_display' => $this->money($p->amount_captured, $p->currency),
            'status' => $p->status,
            'status_badge' => $p->status_badge,
            'payment_method_type' => $p->payment_method_type,
            'customer' => $p->customer ? [
                'id' => $p->customer->id,
                'name' => $p->customer->name,
                'email' => $p->customer->email,
                'public_id' => $p->customer->public_id,
            ] : null,
            'provider' => $p->provider ? [
                'id' => $p->provider->id,
                'code' => $p->provider->code,
                'name' => $p->provider->name,
                'type' => $p->provider->type,
            ] : null,
            'provider_reference' => $p->provider_reference,
            'reference' => $p->reference,
            'attempt_count' => $p->attempt_count,
            'created_at' => $p->created_at?->format('M d, Y H:i'),
            'succeeded_at' => $p->succeeded_at?->format('M d, Y H:i'),
            'failed_at' => $p->failed_at?->format('M d, Y H:i'),
        ];
    }

    protected function formatAttempt(PaymentAttempt $a): array
    {
        return [
            'id' => $a->id,
            'uuid' => $a->uuid,
            'attempt_number' => $a->attempt_number,
            'operation' => $a->operation,
            'operation_label' => $a->operation_label,
            'status' => $a->status,
            'status_badge' => $a->status_badge,
            'amount' => $a->amount,
            'amount_display' => $this->money($a->amount, $a->currency),
            'currency' => $a->currency,
            'provider' => $a->provider ? [
                'id' => $a->provider->id,
                'code' => $a->provider->code,
                'name' => $a->provider->name,
            ] : null,
            'provider_reference' => $a->provider_reference,
            'provider_status' => $a->provider_status,
            'provider_code' => $a->provider_code,
            'provider_message' => $a->provider_message,
            'failure_reason' => $a->failure_reason,
            'duration_ms' => $a->duration_ms,
            'is_fallback' => (bool) $a->is_fallback,
            'is_retry' => (bool) $a->is_retry,
            'request_payload' => $a->request_payload,
            'response_payload' => $a->response_payload,
            'started_at' => $a->started_at?->format('M d, Y H:i:s'),
            'completed_at' => $a->completed_at?->format('M d, Y H:i:s'),
        ];
    }

    protected function formatWebhookEvent(ProviderWebhookEvent $e): array
    {
        return [
            'id' => $e->id,
            'uuid' => $e->uuid,
            'provider' => $e->provider ? [
                'id' => $e->provider->id,
                'code' => $e->provider->code,
                'name' => $e->provider->name,
            ] : null,
            'mode' => $e->mode,
            'event_type' => $e->event_type,
            'provider_event_id' => $e->provider_event_id,
            'provider_reference' => $e->provider_reference,
            'signature_valid' => $e->signature_valid,
            'status' => $e->status,
            'status_badge' => $e->status_badge,
            'process_attempts' => $e->process_attempts,
            'ip_address' => $e->ip_address,
            'processed_at' => $e->processed_at?->format('M d, Y H:i:s'),
            'created_at' => $e->created_at?->format('M d, Y H:i:s'),
        ];
    }

    protected function money(int $minor, string $currency): string
    {
        $zeroDecimal = ['UGX', 'RWF', 'BIF', 'XOF', 'XAF', 'JPY', 'KRW', 'VND', 'CLP', 'ISK', 'XPF'];
        $amount = in_array($currency, $zeroDecimal, true) ? $minor : $minor / 100;

        try {
            return (new \NumberFormatter('en_US', \NumberFormatter::CURRENCY))
                ->formatCurrency($amount, $currency);
        } catch (\Throwable $e) {
            return $currency . ' ' . number_format($amount, 2);
        }
    }
}
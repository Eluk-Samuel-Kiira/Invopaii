<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\Payment;
use App\Services\Payment\PaymentIntentService;
use App\Services\Payment\PaymentProcessor;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method_type' => ['required', 'in:card,mobile_money,bank_transfer,ussd,wallet'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_country' => ['nullable', 'string', 'size:2'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'return_url' => ['nullable', 'url', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ]);

        $companyId = $request->attributes->get('company_id');
        $mode = $request->attributes->get('mode');

        $company = Company::findOrFail($companyId);

        $payment = PaymentIntentService::createAndProcess($company, array_merge($data, [
            'mode' => $mode,
            'source' => 'api',
            'receipt_email' => $data['customer_email'] ?? null,
            'idempotency_key' => $request->header('Idempotency-Key'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]));

        return $this->respond($payment);
    }

    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $payments = Payment::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get();

        return response()->json([
            'data' => $payments->map(fn ($p) => $this->formatPayment($p))->toArray(),
            'has_more' => $payments->count() >= ($request->get('limit') ?? 25),
        ]);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $payment = Payment::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        return $this->respond($payment, true);
    }

    public function attempts(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $payment = Payment::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        return response()->json([
            'data' => $payment->attempts->map(fn ($a) => [
                'id' => $a->uuid,
                'attempt_number' => $a->attempt_number,
                'operation' => $a->operation,
                'status' => $a->status,
                'amount' => $a->amount,
                'currency' => $a->currency,
                'provider_reference' => $a->provider_reference,
                'provider_status' => $a->provider_status,
                'failure_reason' => $a->failure_reason,
                'duration_ms' => $a->duration_ms,
                'created_at' => $a->created_at->toIso8601String(),
            ])->toArray(),
        ]);
    }

    protected function respond(Payment $payment, bool $detailed = false): \Illuminate\Http\JsonResponse
    {
        $payload = $this->formatPayment($payment, $detailed);

        $status = match ($payment->status) {
            'succeeded', 'refunded', 'partially_refunded' => 200,
            'requires_action' => 202,
            'processing' => 202,
            'failed', 'cancelled' => 402,
            default => 200,
        };

        return response()->json($payload, $status);
    }

    protected function formatPayment(Payment $payment, bool $detailed = false): array
    {
        $data = [
            'id' => $payment->public_id,
            'object' => 'payment',
            'status' => $payment->status,
            'amount' => $payment->amount,
            'amount_captured' => $payment->amount_captured,
            'amount_refunded' => $payment->amount_refunded,
            'currency' => $payment->currency,
            'payment_method_type' => $payment->payment_method_type,
            'customer_id' => $payment->customer?->public_id,
            'customer_email' => $payment->receipt_email,
            'reference' => $payment->reference,
            'description' => $payment->description,
            'metadata' => $payment->metadata ?? new \stdClass(),
            'created' => $payment->created_at->toIso8601String(),
        ];

        if ($detailed) {
            $data['provider'] = $payment->provider?->code;
            $data['provider_reference'] = $payment->provider_reference;
            $data['failure_code'] = $payment->failure_code;
            $data['failure_message'] = $payment->failure_message;
            $data['next_action'] = $payment->next_action;
            $data['next_action_type'] = $payment->next_action_type;
            $data['fee_amount'] = $payment->fee_amount;
            $data['net_amount'] = $payment->net_amount;
            $data['succeeded_at'] = $payment->succeeded_at?->toIso8601String();
            $data['captured_at'] = $payment->captured_at?->toIso8601String();
        }

        return $data;
    }
}
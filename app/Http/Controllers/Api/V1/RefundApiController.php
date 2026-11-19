<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment\Payment;
use App\Models\Payment\Refund;
use App\Services\Payment\RefundService;
use Illuminate\Http\Request;

class RefundApiController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_id' => ['required', 'string'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'in:requested_by_customer,duplicate,fraudulent'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = $request->attributes->get('company_id');
        $mode = $request->attributes->get('mode');

        $payment = Payment::where('company_id', $companyId)
            ->where(function ($q) use ($data) {
                $q->where('public_id', $data['payment_id'])
                  ->orWhere('id', $data['payment_id']);
            })
            ->firstOrFail();

        if (!$payment->is_refundable) {
            return response()->json([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => 'This payment is not refundable.',
                    'code' => 'payment_not_refundable',
                ],
            ], 422);
        }

        $amount = $data['amount'] ?? $payment->refundable_amount;

        $refund = RefundService::createForPayment($payment, $amount, [
            'reason' => $data['reason'] ?? 'requested_by_customer',
            'description' => $data['description'] ?? null,
            'source' => 'api',
            'idempotency_key' => $request->header('Idempotency-Key'),
        ]);

        // For the stub provider, immediately mark succeeded.
        // For real providers, this would dispatch a job that calls provider->refund().
        if ($payment->provider?->code === 'stub') {
            $refund = RefundService::markSucceeded($refund);
        }

        return response()->json($this->formatRefund($refund), 201);
    }

    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $refunds = Refund::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get();

        return response()->json([
            'data' => $refunds->map(fn ($r) => $this->formatRefund($r))->toArray(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $refund = Refund::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        return response()->json($this->formatRefund($refund));
    }

    protected function formatRefund(Refund $refund): array
    {
        return [
            'id' => $refund->public_id,
            'object' => 'refund',
            'status' => $refund->status,
            'amount' => $refund->amount,
            'currency' => $refund->currency,
            'payment_id' => $refund->payment?->public_id,
            'reason' => $refund->reason,
            'description' => $refund->description,
            'is_partial' => (bool) $refund->is_partial,
            'created' => $refund->created_at->toIso8601String(),
            'processed_at' => $refund->processed_at?->toIso8601String(),
        ];
    }
}
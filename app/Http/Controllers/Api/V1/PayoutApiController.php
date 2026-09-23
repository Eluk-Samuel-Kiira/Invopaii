<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment\Balance;
use App\Models\Payment\Payout;
use Illuminate\Http\Request;

class PayoutApiController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $payouts = Payout::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get();

        return response()->json([
            'data' => $payouts->map(fn ($p) => $this->format($p))->toArray(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $payout = Payout::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        return response()->json($this->format($payout));
    }

    public function balance(Request $request)
    {
        $companyId = $request->attributes->get('company_id');
        $mode = $request->attributes->get('mode');

        $balances = Balance::where('company_id', $companyId)
            ->where('mode', $mode)
            ->get();

        return response()->json([
            'object' => 'balance',
            'data' => $balances->map(fn ($b) => [
                'currency' => $b->currency,
                'available' => $b->available_amount,
                'pending' => $b->pending_amount,
                'reserved' => $b->reserved_amount,
                'in_transit' => $b->payout_in_transit,
                'total' => $b->total,
            ])->toArray(),
        ]);
    }

    protected function format(Payout $payout): array
    {
        return [
            'id' => $payout->public_id,
            'object' => 'payout',
            'status' => $payout->status,
            'amount' => $payout->amount,
            'currency' => $payout->currency,
            'method' => $payout->method,
            'destination_last_four' => $payout->destination_last_four,
            'expected_arrival_date' => $payout->expected_arrival_date?->toDateString(),
            'paid_at' => $payout->paid_at?->toIso8601String(),
            'created' => $payout->created_at->toIso8601String(),
        ];
    }
}
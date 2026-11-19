<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\AppliedFee;
use Illuminate\Http\Request;

class AppliedFeeController extends Controller
{
    public function index()
    {
        return view('applied-fees.index');
    }

    public function getAppliedFees(Request $request)
    {
        $query = AppliedFee::query()
            ->with([
                'company:id,name,public_id',
                'rule:id,fee_type,payment_method,currency',
                'feeable',
            ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('fee_type')) {
            $query->where('fee_type', $request->fee_type);
        }
        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $fees = $query->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 25));

        return response()->json([
            'current_page' => $fees->currentPage(),
            'data' => collect($fees->items())->map(fn ($f) => $this->format($f))->toArray(),
            'from' => $fees->firstItem(),
            'last_page' => $fees->lastPage(),
            'next_page_url' => $fees->nextPageUrl(),
            'prev_page_url' => $fees->previousPageUrl(),
            'to' => $fees->lastItem(),
            'total' => $fees->total(),
        ]);
    }

    public function getStats(Request $request)
    {
        $base = AppliedFee::query();
        if ($request->filled('company_id')) $base->where('company_id', $request->company_id);
        if ($request->filled('mode')) $base->where('mode', $request->mode);

        $byType = (clone $base)->selectRaw('fee_type, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('fee_type')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->fee_type => [
                'count' => (int) $row->count,
                'total' => (int) $row->total,
            ]]);

        return response()->json([
            'total_count' => (clone $base)->count(),
            'total_amount' => (int) (clone $base)->sum('total_amount'),
            'this_month' => (int) (clone $base)->whereMonth('created_at', now()->month)->sum('total_amount'),
            'by_type' => $byType,
        ]);
    }

    public function getAppliedFee($id)
    {
        try {
            $fee = AppliedFee::with(['company', 'rule', 'feeable'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($fee), [
                    'calculation_snapshot' => $fee->calculation_snapshot,
                    'description' => $fee->description,
                    'base_amount' => $fee->base_amount,
                    'percentage_applied' => (string) $fee->percentage_applied,
                    'percentage_component' => $fee->percentage_component,
                    'fixed_component' => $fee->fixed_component,
                    'tax_amount' => $fee->tax_amount,
                    'total_amount' => $fee->total_amount,
                    'is_passed_to_customer' => (bool) $fee->is_passed_to_customer,
                    'is_waived' => (bool) $fee->is_waived,
                    'waiver_reason' => $fee->waiver_reason,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fee not found'], 404);
        }
    }

    public function getFormOptions()
    {
        return response()->json([
            'companies' => Company::orderBy('name')->get(['id', 'name', 'public_id'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => "{$c->name} ({$c->public_id})"]),
            'fee_types' => [
                ['value' => 'processing', 'label' => 'Processing'],
                ['value' => 'refund', 'label' => 'Refund'],
                ['value' => 'payout', 'label' => 'Payout'],
                ['value' => 'chargeback', 'label' => 'Chargeback'],
                ['value' => 'fx', 'label' => 'FX'],
                ['value' => 'international_card', 'label' => 'International Card'],
                ['value' => 'monthly', 'label' => 'Monthly'],
            ],
        ]);
    }

    protected function format(AppliedFee $f): array
    {
        return [
            'id' => $f->id,
            'uuid' => $f->uuid,
            'company' => $f->company ? [
                'id' => $f->company->id,
                'name' => $f->company->name,
                'public_id' => $f->company->public_id,
            ] : null,
            'mode' => $f->mode,
            'feeable_type' => $f->feeable_type ? class_basename($f->feeable_type) : null,
            'feeable_public_id' => $f->feeable?->public_id,
            'fee_type' => $f->fee_type,
            'fee_type_label' => $f->fee_type_label,
            'currency' => $f->currency,
            'total_amount' => $f->total_amount,
            'base_amount' => $f->base_amount,
            'tax_amount' => $f->tax_amount,
            'description' => $f->description,
            'created_at' => $f->created_at?->format('M d, Y H:i'),
        ];
    }
}
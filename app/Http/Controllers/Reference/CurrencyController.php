<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Models\Reference\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    public function index()
    {
        return view('admin.reference.currencies.index');
    }

    public function getCurrencies(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        $currencies = Currency::query()
            ->search($search)
            ->orderBy('code')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $currencies->currentPage(),
            'data' => collect($currencies->items())->map(fn ($c) => $this->formatCurrency($c))->toArray(),
            'first_page_url' => $currencies->url(1),
            'from' => $currencies->firstItem(),
            'last_page' => $currencies->lastPage(),
            'last_page_url' => $currencies->url($currencies->lastPage()),
            'next_page_url' => $currencies->nextPageUrl(),
            'prev_page_url' => $currencies->previousPageUrl(),
            'to' => $currencies->lastItem(),
            'total' => $currencies->total(),
            'per_page' => $perPage,
        ]);
    }

    public function getCurrency($id)
    {
        try {
            $currency = Currency::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatCurrency($currency),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Currency not found'], 404);
        }
    }

    public function storeCurrency(Request $request)
    {
        $data = $this->validated($request);

        try {
            $currency = Currency::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Currency created successfully',
                'data' => $this->formatCurrency($currency),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateCurrency(Request $request, $id)
    {
        try {
            $currency = Currency::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Currency not found'], 404);
        }

        $data = $this->validated($request, $currency->id);

        try {
            $currency->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully',
                'data' => $this->formatCurrency($currency->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCurrency($id)
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete currency: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle one of: is_active, is_settlement_currency, is_presentment_currency, is_zero_decimal.
     */
    public function toggleFlag(Request $request, $id)
    {
        $request->validate([
            'field' => ['required', Rule::in([
                'is_active',
                'is_settlement_currency',
                'is_presentment_currency',
                'is_zero_decimal',
            ])],
        ]);

        try {
            $currency = Currency::findOrFail($id);
            $field = $request->field;

            // Guard: must remain at least one presentment currency active
            if ($field === 'is_presentment_currency' && $currency->$field && Currency::where('is_presentment_currency', true)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one presentment currency must remain enabled.',
                ], 422);
            }

            // Guard: must remain at least one settlement currency
            if ($field === 'is_settlement_currency' && $currency->$field && Currency::where('is_settlement_currency', true)->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one settlement currency must remain enabled.',
                ], 422);
            }

            $currency->$field = !$currency->$field;

            // Cascade: zero-decimal → exponent must be 0
            if ($field === 'is_zero_decimal' && $currency->is_zero_decimal) {
                $currency->exponent = 0;
            }

            $currency->save();

            return response()->json([
                'success' => true,
                'message' => 'Flag updated successfully',
                'data' => $this->formatCurrency($currency),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle flag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ---------- Helpers ---------- */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('currencies', 'code');
        if ($ignoreId) $codeRule->ignore($ignoreId);

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:3', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:8'],
            'exponent' => ['required', 'integer', 'min:0', 'max:4'],
            'is_zero_decimal' => ['boolean'],
            'is_active' => ['boolean'],
            'is_settlement_currency' => ['boolean'],
            'is_presentment_currency' => ['boolean'],
            'min_charge_amount' => ['nullable', 'integer', 'min:0'],
            'max_charge_amount' => ['nullable', 'integer', 'min:0', 'gte:min_charge_amount'],
        ]);

        $validated['code'] = strtoupper($validated['code']);

        // Coerce booleans
        foreach (['is_zero_decimal', 'is_active', 'is_settlement_currency', 'is_presentment_currency'] as $flag) {
            $validated[$flag] = (bool) ($validated[$flag] ?? false);
        }

        // Enforce: zero-decimal → exponent 0
        if ($validated['is_zero_decimal']) {
            $validated['exponent'] = 0;
        }

        return $validated;
    }

    protected function formatCurrency(Currency $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'symbol' => $c->symbol,
            'exponent' => (int) $c->exponent,
            'decimal_places' => $c->decimal_places,
            'minor_unit_label' => $c->minor_unit_label,
            'is_zero_decimal' => (bool) $c->is_zero_decimal,
            'is_active' => (bool) $c->is_active,
            'is_settlement_currency' => (bool) $c->is_settlement_currency,
            'is_presentment_currency' => (bool) $c->is_presentment_currency,
            'min_charge_amount' => $c->min_charge_amount,
            'max_charge_amount' => $c->max_charge_amount,
            'min_charge_display' => $c->formatMinor($c->min_charge_amount),
            'max_charge_display' => $c->formatMinor($c->max_charge_amount),
            'updated_at' => $c->updated_at?->format('M d, Y H:i'),
            'created_at' => $c->created_at?->format('M d, Y'),
        ];
    }
}
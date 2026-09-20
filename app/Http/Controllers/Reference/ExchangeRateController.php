<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Models\Reference\ExchangeRate;
use App\Models\Reference\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExchangeRateController extends Controller
{
    public function index()
    {
        return view('admin.reference.exchange-rates.index');
    }

    public function getExchangeRates(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        $rates = ExchangeRate::query()
            ->search($search)
            ->orderByDesc('effective_from')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $rates->currentPage(),
            'data' => collect($rates->items())->map(fn ($r) => $this->formatRate($r))->toArray(),
            'first_page_url' => $rates->url(1),
            'from' => $rates->firstItem(),
            'last_page' => $rates->lastPage(),
            'last_page_url' => $rates->url($rates->lastPage()),
            'next_page_url' => $rates->nextPageUrl(),
            'prev_page_url' => $rates->previousPageUrl(),
            'to' => $rates->lastItem(),
            'total' => $rates->total(),
            'per_page' => $perPage,
        ]);
    }

    public function getExchangeRate($id)
    {
        try {
            $rate = ExchangeRate::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatRate($rate),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exchange rate not found'], 404);
        }
    }

    /**
     * Return the currently-active rate for a given pair.
     * Used by the UI to preview rates, and useful for the API later.
     */
    public function getCurrentRate(Request $request)
    {
        $request->validate([
            'base' => ['required', 'string', 'size:3'],
            'quote' => ['required', 'string', 'size:3'],
        ]);

        $rate = ExchangeRate::pair($request->base, $request->quote)
            ->current()
            ->first();

        if (!$rate) {
            return response()->json([
                'success' => false,
                'message' => 'No active rate found for this pair.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRate($rate),
        ]);
    }

    public function storeExchangeRate(Request $request)
    {
        $data = $this->validated($request);

        try {
            $rate = ExchangeRate::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate created successfully',
                'data' => $this->formatRate($rate),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateExchangeRate(Request $request, $id)
    {
        try {
            $rate = ExchangeRate::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Exchange rate not found'], 404);
        }

        $data = $this->validated($request, $rate->id);

        try {
            $rate->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate updated successfully',
                'data' => $this->formatRate($rate->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteExchangeRate($id)
    {
        try {
            $rate = ExchangeRate::findOrFail($id);
            $rate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exchange rate deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exchange rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Close an open-ended rate (set effective_to = now).
     */
    public function closeRate($id)
    {
        try {
            $rate = ExchangeRate::findOrFail($id);

            if ($rate->effective_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This rate is already closed.',
                ], 422);
            }

            if ($rate->effective_from > now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot close a rate that has not yet started.',
                ], 422);
            }

            $rate->update(['effective_to' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Rate closed successfully',
                'data' => $this->formatRate($rate->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return list of currency codes (used to populate dropdowns).
     */
    public function getCurrencyCodes()
    {
        $codes = Currency::where('is_active', true)
            ->orderBy('code')
            ->get(['code', 'name'])
            ->map(fn ($c) => ['code' => $c->code, 'label' => "{$c->code} — {$c->name}"]);

        return response()->json($codes);
    }

    /* ---------- Helpers ---------- */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3', 'different:base_currency'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'markup_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'provider' => ['nullable', 'string', 'max:64'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
        ]);

        $validated['base_currency'] = strtoupper($validated['base_currency']);
        $validated['quote_currency'] = strtoupper($validated['quote_currency']);
        $validated['markup_percent'] = $validated['markup_percent'] ?? 0;

        // Uniqueness: same pair can't have two rates with the same effective_from
        $existing = ExchangeRate::where('base_currency', $validated['base_currency'])
            ->where('quote_currency', $validated['quote_currency'])
            ->where('effective_from', $validated['effective_from'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($existing) {
            abort(response()->json([
                'success' => false,
                'message' => 'A rate for this pair with this effective_from already exists.',
                'errors' => ['effective_from' => ['Duplicate effective_from for this pair.']],
            ], 422));
        }

        return $validated;
    }

    protected function formatRate(ExchangeRate $r): array
    {
        return [
            'id' => $r->id,
            'base_currency' => $r->base_currency,
            'quote_currency' => $r->quote_currency,
            'pair' => $r->pair,
            'rate' => (string) $r->rate,
            'markup_percent' => (string) $r->markup_percent,
            'effective_rate' => (string) $r->effective_rate,
            'provider' => $r->provider,
            'effective_from' => $r->effective_from?->toIso8601String(),
            'effective_to' => $r->effective_to?->toIso8601String(),
            'effective_from_display' => $r->effective_from?->format('M d, Y H:i'),
            'effective_to_display' => $r->effective_to?->format('M d, Y H:i'),
            'is_active' => $r->is_active,
            'status' => $r->status,
            'created_at' => $r->created_at?->format('M d, Y'),
        ];
    }
}
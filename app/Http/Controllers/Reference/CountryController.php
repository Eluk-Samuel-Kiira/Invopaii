<?php

namespace App\Http\Controllers\Reference;

use App\Http\Controllers\Controller;
use App\Models\Reference\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    /**
     * Display the countries list page.
     */
    public function index()
    {
        return view('admin.reference.countries.index');
    }

    /**
     * JSON: paginated + searchable list.
     */
    public function getCountries(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        $countries = Country::query()
            ->search($search)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $countries->currentPage(),
            'data' => collect($countries->items())->map(fn ($c) => $this->formatCountry($c))->toArray(),
            'first_page_url' => $countries->url(1),
            'from' => $countries->firstItem(),
            'last_page' => $countries->lastPage(),
            'last_page_url' => $countries->url($countries->lastPage()),
            'next_page_url' => $countries->nextPageUrl(),
            'prev_page_url' => $countries->previousPageUrl(),
            'to' => $countries->lastItem(),
            'total' => $countries->total(),
            'per_page' => $perPage,
        ]);
    }

    /**
     * JSON: single country for edit.
     */
    public function getCountry($id)
    {
        try {
            $country = Country::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->formatCountry($country), [
                    'official_name' => $country->official_name,
                    'required_business_documents' => $country->required_business_documents ?? [],
                    'supported_payment_methods' => $country->supported_payment_methods ?? [],
                    'metadata' => $country->metadata,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }
    }

    /**
     * Store a new country.
     */
    public function storeCountry(Request $request)
    {
        $data = $this->validated($request);

        try {
            $country = Country::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Country created successfully',
                'data' => $this->formatCountry($country),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create country: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing country.
     */
    public function updateCountry(Request $request, $id)
    {
        try {
            $country = Country::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $data = $this->validated($request, $country->id);

        try {
            $country->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Country updated successfully',
                'data' => $this->formatCountry($country->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update country: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a country.
     */
    public function deleteCountry($id)
    {
        try {
            $country = Country::findOrFail($id);
            $country->delete();

            return response()->json([
                'success' => true,
                'message' => 'Country deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete country: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle a boolean flag (is_supported, collections_enabled, payouts_enabled, is_high_risk, is_sanctioned).
     */
    public function toggleFlag(Request $request, $id)
    {
        $request->validate([
            'field' => ['required', Rule::in([
                'is_supported',
                'collections_enabled',
                'payouts_enabled',
                'is_high_risk',
                'is_sanctioned',
            ])],
        ]);

        try {
            $country = Country::findOrFail($id);
            $field = $request->field;

            // Sanity: sanctioned countries can't be supported/collecting/payouts
            if ($field !== 'is_sanctioned' && $country->is_sanctioned && !$country->$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sanctioned countries cannot enable this flag.',
                ], 422);
            }

            $country->$field = !$country->$field;

            // Cascade: turning off is_supported turns off collections & payouts
            if ($field === 'is_supported' && $country->is_supported === false) {
                $country->collections_enabled = false;
                $country->payouts_enabled = false;
            }

            // Cascade: turning on collections requires supported
            if ($field === 'collections_enabled' && $country->collections_enabled === true) {
                $country->is_supported = true;
            }

            // Cascade: turning on payouts requires supported + collections
            if ($field === 'payouts_enabled' && $country->payouts_enabled === true) {
                $country->is_supported = true;
                $country->collections_enabled = true;
            }

            $country->save();

            return response()->json([
                'success' => true,
                'message' => 'Flag updated successfully',
                'data' => $this->formatCountry($country),
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
        $iso2Rule = Rule::unique('countries', 'iso2');
        $iso3Rule = Rule::unique('countries', 'iso3');

        if ($ignoreId) {
            $iso2Rule->ignore($ignoreId);
            $iso3Rule->ignore($ignoreId);
        }

        $validated = $request->validate([
            'iso2' => ['required', 'string', 'size:2', $iso2Rule],
            'iso3' => ['required', 'string', 'size:3', $iso3Rule],
            'name' => ['required', 'string', 'max:255'],
            'official_name' => ['nullable', 'string', 'max:255'],
            'phone_code' => ['nullable', 'string', 'max:8'],
            'default_currency' => ['nullable', 'string', 'size:3'],
            'region' => ['nullable', 'string', 'max:64'],
            'subregion' => ['nullable', 'string', 'max:64'],
            'flag_emoji' => ['nullable', 'string', 'max:16'],
            'is_supported' => ['boolean'],
            'collections_enabled' => ['boolean'],
            'payouts_enabled' => ['boolean'],
            'is_high_risk' => ['boolean'],
            'is_sanctioned' => ['boolean'],
            'required_business_documents' => ['nullable', 'array'],
            'supported_payment_methods' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $validated['iso2'] = strtoupper($validated['iso2']);
        $validated['iso3'] = strtoupper($validated['iso3']);
        if (!empty($validated['default_currency'])) {
            $validated['default_currency'] = strtoupper($validated['default_currency']);
        }

        // Coerce booleans for checkbox-style inputs
        foreach (['is_supported', 'collections_enabled', 'payouts_enabled', 'is_high_risk', 'is_sanctioned'] as $flag) {
            $validated[$flag] = (bool) ($validated[$flag] ?? false);
        }

        // Enforce sanctioned => not supported
        if (!empty($validated['is_sanctioned'])) {
            $validated['is_supported'] = false;
            $validated['collections_enabled'] = false;
            $validated['payouts_enabled'] = false;
        }

        return $validated;
    }

    protected function formatCountry(Country $c): array
    {
        return [
            'id' => $c->id,
            'iso2' => $c->iso2,
            'iso3' => $c->iso3,
            'name' => $c->name,
            'official_name' => $c->official_name,
            'phone_code' => $c->phone_code,
            'default_currency' => $c->default_currency,
            'region' => $c->region,
            'subregion' => $c->subregion,
            'flag_emoji' => $c->flag_emoji,
            'display_name' => $c->display_name,
            'market_status' => $c->market_status,
            'is_supported' => (bool) $c->is_supported,
            'collections_enabled' => (bool) $c->collections_enabled,
            'payouts_enabled' => (bool) $c->payouts_enabled,
            'is_high_risk' => (bool) $c->is_high_risk,
            'is_sanctioned' => (bool) $c->is_sanctioned,
            'supported_payment_methods' => $c->supported_payment_methods ?? [],
            'required_business_documents' => $c->required_business_documents ?? [],
            'updated_at' => $c->updated_at?->format('M d, Y H:i'),
            'created_at' => $c->created_at?->format('M d, Y'),
        ];
    }
}
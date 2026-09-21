<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Discount;
use App\Models\Catalog\Price;
use App\Models\Catalog\Product;
use App\Models\Catalog\TaxRate;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       PRODUCTS
       ═══════════════════════════════════════════════════════ */

    public function getProducts(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $query = $company->products()
                ->withCount('prices')
                ->search($request->get('search'));

            if ($request->filled('mode')) $query->where('mode', $request->mode);
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            $products = $query->orderByDesc('created_at')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'current_page' => $products->currentPage(),
                'data' => collect($products->items())->map(fn ($p) => $this->formatProduct($p))->toArray(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getProduct($id)
    {
        try {
            $product = Product::with(['prices' => fn ($q) => $q->orderByDesc('is_active')->orderBy('unit_amount')])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->formatProduct($product), [
                    'description' => $product->description,
                    'image_path' => $product->image_path,
                    'sku' => $product->sku,
                    'unit_label' => $product->unit_label,
                    'is_shippable' => (bool) $product->is_shippable,
                    'tax_code' => $product->tax_code,
                    'metadata' => $product->metadata,
                    'prices' => $product->prices->map(fn ($pr) => $this->formatPrice($pr))->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
    }

    public function storeProduct(Request $request, $companyId)
    {
        $data = $this->validatedProduct($request);

        try {
            $company = Company::findOrFail($companyId);
            $product = $company->products()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Product created.',
                'data' => $this->formatProduct($product->fresh()),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProduct(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $data = $this->validatedProduct($request, $product->id);

        try {
            $product->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Product updated.',
                'data' => $this->formatProduct($product->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteProduct($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(['success' => true, 'message' => 'Product deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       PRICES
       ═══════════════════════════════════════════════════════ */

    public function getPrices(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);
            $prices = $product->prices()->orderByDesc('is_active')->orderBy('unit_amount')->get();

            return response()->json([
                'success' => true,
                'data' => $prices->map(fn ($p) => $this->formatPrice($p))->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }
    }

    public function storePrice(Request $request, $productId)
    {
        $data = $this->validatedPrice($request);

        try {
            $product = Product::findOrFail($productId);
            $data['company_id'] = $product->company_id;
            $data['mode'] = $product->mode;

            $price = $product->prices()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Price added.',
                'data' => $this->formatPrice($price),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updatePrice(Request $request, $id)
    {
        try {
            $price = Price::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Price not found'], 404);
        }

        $data = $this->validatedPrice($request);

        try {
            $price->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Price updated.',
                'data' => $this->formatPrice($price->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deletePrice($id)
    {
        try {
            $price = Price::findOrFail($id);
            $price->delete();

            return response()->json(['success' => true, 'message' => 'Price deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       TAX RATES
       ═══════════════════════════════════════════════════════ */

    public function getTaxRates(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $rates = $company->taxRates()
                ->orderByDesc('is_active')
                ->orderBy('display_name')
                ->get()
                ->map(fn ($t) => $this->formatTaxRate($t));

            return response()->json(['success' => true, 'data' => $rates]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeTaxRate(Request $request, $companyId)
    {
        $data = $this->validatedTaxRate($request);

        try {
            $company = Company::findOrFail($companyId);
            $rate = $company->taxRates()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Tax rate created.',
                'data' => $this->formatTaxRate($rate),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateTaxRate(Request $request, $id)
    {
        try {
            $rate = TaxRate::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tax rate not found'], 404);
        }

        $data = $this->validatedTaxRate($request);

        try {
            $rate->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Tax rate updated.',
                'data' => $this->formatTaxRate($rate->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteTaxRate($id)
    {
        try {
            $rate = TaxRate::findOrFail($id);
            $rate->delete();

            return response()->json(['success' => true, 'message' => 'Tax rate deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       DISCOUNTS
       ═══════════════════════════════════════════════════════ */

    public function getDiscounts(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $discounts = $company->discounts()
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($d) => $this->formatDiscount($d));

            return response()->json(['success' => true, 'data' => $discounts]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeDiscount(Request $request, $companyId)
    {
        $data = $this->validatedDiscount($request);

        try {
            $company = Company::findOrFail($companyId);

            // Unique code per (company, mode)
            if (!empty($data['code'])) {
                $exists = $company->discounts()
                    ->where('mode', $data['mode'])
                    ->where('code', $data['code'])
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A discount with this code already exists.',
                    ], 422);
                }
            }

            $discount = $company->discounts()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Discount created.',
                'data' => $this->formatDiscount($discount),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateDiscount(Request $request, $id)
    {
        try {
            $discount = Discount::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Discount not found'], 404);
        }

        $data = $this->validatedDiscount($request, $discount->id);

        try {
            if (!empty($data['code']) && $data['code'] !== $discount->code) {
                $exists = Discount::where('company_id', $discount->company_id)
                    ->where('mode', $discount->mode)
                    ->where('code', $data['code'])
                    ->where('id', '!=', $discount->id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Another discount with this code already exists.',
                    ], 422);
                }
            }

            $discount->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Discount updated.',
                'data' => $this->formatDiscount($discount->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteDiscount($id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->delete();

            return response()->json(['success' => true, 'message' => 'Discount deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /**
     * Validate a discount code and compute the amount — used inline from invoices.
     */
    public function validateDiscountCode(Request $request, $companyId)
    {
        $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'mode' => ['required', Rule::in(['test', 'live'])],
        ]);

        $discount = Discount::where('company_id', $companyId)
            ->where('mode', $request->mode)
            ->where('code', $request->code)
            ->first();

        if (!$discount) {
            return response()->json(['success' => false, 'message' => 'Invalid discount code.'], 404);
        }

        if (!$discount->is_valid) {
            return response()->json(['success' => false, 'message' => 'This discount is no longer valid.'], 422);
        }

        $amount = $discount->computeDiscount((int) $request->subtotal);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $discount->id,
                'code' => $discount->code,
                'label' => $discount->label,
                'amount_discounted' => $amount,
            ],
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validatedProduct(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:64'],
            'unit_label' => ['nullable', 'string', 'max:32'],
            'is_active' => ['boolean'],
            'is_shippable' => ['boolean'],
            'tax_code' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    protected function validatedPrice(Request $request): array
    {
        return $request->validate([
            'nickname' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'unit_amount' => ['required', 'integer', 'min:0'],
            'billing_scheme' => ['required', Rule::in(['per_unit', 'tiered'])],
            'tiers' => ['nullable', 'array'],
            'type' => ['required', Rule::in(['one_time', 'recurring'])],
            'recurring_interval' => ['nullable', Rule::in(['day', 'week', 'month', 'year'])],
            'recurring_interval_count' => ['nullable', 'integer', 'min:1', 'max:365'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'tax_inclusive' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    protected function validatedTaxRate(Request $request): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'display_name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_inclusive' => ['boolean'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'state' => ['nullable', 'string', 'max:64'],
            'jurisdiction' => ['nullable', 'string', 'max:64'],
            'tax_type' => ['nullable', Rule::in(['vat', 'gst', 'sales_tax', 'withholding'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    protected function validatedDiscount(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'code' => ['nullable', 'string', 'max:64', 'regex:/^[A-Z0-9_-]+$/i'],
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percentage', 'fixed_amount'])],
            'percent_off' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_if:type,percentage'],
            'amount_off' => ['nullable', 'integer', 'min:0', 'required_if:type,fixed_amount'],
            'currency' => ['nullable', 'string', 'size:3', 'required_if:type,fixed_amount'],
            'duration' => ['required', Rule::in(['once', 'repeating', 'forever'])],
            'duration_in_months' => ['nullable', 'integer', 'min:1', 'max:60'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'max_redemptions_per_customer' => ['nullable', 'integer', 'min:1'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
            'applies_to_product_ids' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (!empty($data['code'])) $data['code'] = strtoupper($data['code']);

        return $data;
    }

    protected function formatProduct(Product $p): array
    {
        return [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'public_id' => $p->public_id,
            'company_id' => $p->company_id,
            'mode' => $p->mode,
            'name' => $p->name,
            'description' => $p->description,
            'sku' => $p->sku,
            'unit_label' => $p->unit_label,
            'is_active' => (bool) $p->is_active,
            'is_shippable' => (bool) $p->is_shippable,
            'prices_count' => $p->prices_count ?? null,
            'created_at' => $p->created_at?->format('M d, Y'),
        ];
    }

    protected function formatPrice(Price $p): array
    {
        return [
            'id' => $p->id,
            'uuid' => $p->uuid,
            'public_id' => $p->public_id,
            'product_id' => $p->product_id,
            'nickname' => $p->nickname,
            'currency' => $p->currency,
            'unit_amount' => $p->unit_amount,
            'unit_amount_display' => number_format($p->unit_amount / 100, 2),
            'billing_scheme' => $p->billing_scheme,
            'type' => $p->type,
            'recurring_interval' => $p->recurring_interval,
            'recurring_interval_count' => $p->recurring_interval_count,
            'interval_label' => $p->interval_label,
            'trial_period_days' => $p->trial_period_days,
            'is_active' => (bool) $p->is_active,
            'tax_inclusive' => (bool) $p->tax_inclusive,
            'created_at' => $p->created_at?->format('M d, Y'),
        ];
    }

    protected function formatTaxRate(TaxRate $t): array
    {
        return [
            'id' => $t->id,
            'uuid' => $t->uuid,
            'public_id' => $t->public_id,
            'company_id' => $t->company_id,
            'mode' => $t->mode,
            'display_name' => $t->display_name,
            'percentage' => (string) $t->percentage,
            'label' => $t->label,
            'is_inclusive' => (bool) $t->is_inclusive,
            'country_code' => $t->country_code,
            'state' => $t->state,
            'jurisdiction' => $t->jurisdiction,
            'tax_type' => $t->tax_type,
            'description' => $t->description,
            'is_active' => (bool) $t->is_active,
            'created_at' => $t->created_at?->format('M d, Y'),
        ];
    }

    protected function formatDiscount(Discount $d): array
    {
        return [
            'id' => $d->id,
            'uuid' => $d->uuid,
            'public_id' => $d->public_id,
            'company_id' => $d->company_id,
            'mode' => $d->mode,
            'code' => $d->code,
            'name' => $d->name,
            'type' => $d->type,
            'label' => $d->label,
            'percent_off' => $d->percent_off ? (string) $d->percent_off : null,
            'amount_off' => $d->amount_off,
            'currency' => $d->currency,
            'duration' => $d->duration,
            'duration_in_months' => $d->duration_in_months,
            'max_redemptions' => $d->max_redemptions,
            'times_redeemed' => $d->times_redeemed,
            'max_redemptions_per_customer' => $d->max_redemptions_per_customer,
            'minimum_order_amount' => $d->minimum_order_amount,
            'starts_at' => $d->starts_at?->format('M d, Y H:i'),
            'expires_at' => $d->expires_at?->format('M d, Y H:i'),
            'is_active' => (bool) $d->is_active,
            'is_valid' => $d->is_valid,
            'created_at' => $d->created_at?->format('M d, Y'),
        ];
    }
}
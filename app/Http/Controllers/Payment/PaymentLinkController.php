<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentLinkController extends Controller
{
    /**
     * Render the list page.
     */
    public function index(Request $request)
    {
        return view('company2.payment-links.index');
    }

    /**
     * Just enough company data for the stripped list.
     */
    public function getCompanies(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);

        $query = \App\Models\Company\Company::query()
            ->with('country:id,name,iso2,flag_emoji')
            ->withCount('paymentLinks');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('public_id', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $companies->currentPage(),
            'data' => collect($companies->items())->map(fn ($c) => [
                'id' => $c->id,
                'public_id' => $c->public_id,
                'name' => $c->name,
                'email' => $c->email,
                'brand_color' => $c->brand_color,
                'country' => $c->country ? [
                    'name' => $c->country->name,
                    'iso2' => $c->country->iso2,
                    'flag_emoji' => $c->country->flag_emoji,
                ] : null,
                'default_currency' => $c->default_currency,
                'status' => $c->status,
                'status_badge' => $c->status_badge,
                'payment_links_count' => $c->payment_links_count,
            ])->toArray(),
            'from' => $companies->firstItem(),
            'last_page' => $companies->lastPage(),
            'next_page_url' => $companies->nextPageUrl(),
            'prev_page_url' => $companies->previousPageUrl(),
            'to' => $companies->lastItem(),
            'total' => $companies->total(),
        ]);
    }

    /**
     * JSON: list payment links for a single company.
     */
    public function getPaymentLinks(Request $request, $companyId)
    {
        try {
            $company = \App\Models\Company\Company::findOrFail($companyId);

            $query = $company->paymentLinks()
                ->with('createdBy:id,name')
                ->search($request->get('search'));

            if ($request->filled('mode')) {
                $query->where('mode', $request->mode);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('amount_type')) {
                $query->where('amount_type', $request->amount_type);
            }

            $links = $query->orderByDesc('created_at')->get();

            return response()->json([
                'success' => true,
                'data' => $links->map(fn ($l) => $this->format($l))->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    /**
     * Stats for the modal header.
     */
    public function getCompanyStats($companyId)
    {
        try {
            $company = \App\Models\Company\Company::findOrFail($companyId);

            $base = $company->paymentLinks();

            return response()->json([
                'total'           => (clone $base)->count(),
                'active'          => (clone $base)->where('status', 'active')->count(),
                'expired'         => (clone $base)->where('status', 'expired')->count(),
                'completed'       => (clone $base)->where('status', 'completed')->count(),
                'total_collected' => (int) (clone $base)->sum('amount_collected'),
                'total_payments'  => (int) (clone $base)->sum('payments_count'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    /**
     * JSON: single link for edit.
     */
    public function getPaymentLink($id)
    {
        try {
            $link = PaymentLink::with('items', 'company:id,name,public_id')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($link), [
                    'description' => $link->description,
                    'minimum_amount' => $link->minimum_amount,
                    'maximum_amount' => $link->maximum_amount,
                    'suggested_amounts' => $link->suggested_amounts ?? [],
                    'collect_customer_name' => (bool) $link->collect_customer_name,
                    'collect_email' => (bool) $link->collect_email,
                    'collect_phone' => (bool) $link->collect_phone,
                    'collect_billing_address' => (bool) $link->collect_billing_address,
                    'collect_shipping_address' => (bool) $link->collect_shipping_address,
                    'max_payments' => $link->max_payments,
                    'active_from' => $link->active_from?->toIso8601String(),
                    'after_completion' => $link->after_completion,
                    'success_url' => $link->success_url,
                    'cancel_url' => $link->cancel_url,
                    'success_message' => $link->success_message,
                    'send_receipt' => (bool) $link->send_receipt,
                    'reference_prefix' => $link->reference_prefix,
                    'statement_descriptor' => $link->statement_descriptor,
                    'metadata' => $link->metadata,
                    'items' => $link->items->map(fn ($i) => $i->toArray())->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment link not found'], 404);
        }
    }

    public function storePaymentLink(Request $request)
    {
        $data = $this->validated($request);

        try {
            $link = PaymentLink::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Payment link created.',
                'data' => $this->format($link->fresh(['company'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updatePaymentLink(Request $request, $id)
    {
        try {
            $link = PaymentLink::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment link not found'], 404);
        }

        $data = $this->validated($request, $link->id);

        try {
            $link->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Payment link updated.',
                'data' => $this->format($link->fresh(['company'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function togglePaymentLink($id)
    {
        try {
            $link = PaymentLink::findOrFail($id);

            $link->status = $link->status === 'active' ? 'inactive' : 'active';
            $link->save();

            return response()->json([
                'success' => true,
                'message' => "Link {$link->status}.",
                'data' => $this->format($link->fresh(['company'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle'], 500);
        }
    }

    public function duplicatePaymentLink($id)
    {
        try {
            $source = PaymentLink::with('items')->findOrFail($id);

            $copy = $source->replicate(['uuid', 'public_id', 'slug', 'payments_count', 'amount_collected']);
            $copy->title = $source->title . ' (copy)';
            $copy->status = 'inactive';
            $copy->slug = null;
            $copy->public_id = null;
            $copy->uuid = null;
            $copy->save();

            foreach ($source->items as $item) {
                $copy->items()->create($item->only([
                    'price_id', 'product_id', 'name', 'description',
                    'unit_amount', 'currency', 'quantity', 'min_quantity', 'max_quantity',
                    'is_adjustable', 'tax_rate_id', 'sort_order',
                ]));
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment link duplicated.',
                'data' => $this->format($copy->fresh(['company'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deletePaymentLink($id)
    {
        try {
            $link = PaymentLink::findOrFail($id);
            $link->delete();

            return response()->json(['success' => true, 'message' => 'Payment link deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ---------- Helpers ---------- */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'amount_type' => ['required', Rule::in(['fixed', 'customer_chooses', 'line_items'])],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['nullable', 'integer', 'min:0', 'required_if:amount_type,fixed'],
            'minimum_amount' => ['nullable', 'integer', 'min:0'],
            'maximum_amount' => ['nullable', 'integer', 'min:0', 'gte:minimum_amount'],
            'suggested_amounts' => ['nullable', 'array'],
            'suggested_amounts.*' => ['integer', 'min:0'],
            'collect_customer_name' => ['boolean'],
            'collect_email' => ['boolean'],
            'collect_phone' => ['boolean'],
            'collect_billing_address' => ['boolean'],
            'collect_shipping_address' => ['boolean'],
            'usage_type' => ['required', Rule::in(['single_use', 'multi_use'])],
            'max_payments' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'after_completion' => ['required', Rule::in(['hosted_confirmation', 'redirect'])],
            'success_url' => ['nullable', 'url', 'max:2048', 'required_if:after_completion,redirect'],
            'cancel_url' => ['nullable', 'url', 'max:2048'],
            'success_message' => ['nullable', 'string', 'max:1000'],
            'send_receipt' => ['boolean'],
            'reference_prefix' => ['nullable', 'string', 'max:24'],
            'statement_descriptor' => ['nullable', 'string', 'max:22'],
        ]);

        $validated['collect_customer_name'] = $validated['collect_customer_name'] ?? true;
        $validated['collect_email'] = $validated['collect_email'] ?? true;
        $validated['collect_phone'] = $validated['collect_phone'] ?? false;
        $validated['collect_billing_address'] = $validated['collect_billing_address'] ?? false;
        $validated['collect_shipping_address'] = $validated['collect_shipping_address'] ?? false;
        $validated['send_receipt'] = $validated['send_receipt'] ?? true;
        $validated['created_by_id'] = auth()->id();

        return $validated;
    }

    protected function format(PaymentLink $l): array
    {
        return [
            'id' => $l->id,
            'uuid' => $l->uuid,
            'public_id' => $l->public_id,
            'company_id' => $l->company_id,
            'company' => $l->company ? [
                'id' => $l->company->id,
                'name' => $l->company->name,
                'public_id' => $l->company->public_id,
            ] : null,
            'mode' => $l->mode,
            'mode_badge' => $l->mode_badge,
            'slug' => $l->slug,
            'title' => $l->title,
            'description' => $l->description,
            'amount_type' => $l->amount_type,
            'amount_type_label' => $l->amount_type_label,
            'currency' => $l->currency,
            'amount' => $l->amount,
            'amount_display' => $l->amount !== null ? $this->money($l->amount, $l->currency) : null,
            'amount_collected' => $l->amount_collected,
            'amount_collected_display' => $this->money($l->amount_collected, $l->currency),
            'usage_type' => $l->usage_type,
            'payments_count' => $l->payments_count,
            'max_payments' => $l->max_payments,
            'status' => $l->status,
            'status_badge' => $l->status_badge,
            'is_expired' => $l->is_expired,
            'expires_at' => $l->expires_at?->format('M d, Y'),
            'public_url' => $l->public_url,
            'created_by' => $l->createdBy?->name,
            'created_at' => $l->created_at?->format('M d, Y'),
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
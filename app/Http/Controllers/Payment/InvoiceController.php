<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Discount;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Payment\Invoice;
use App\Models\Payment\InvoiceItem;
use App\Models\Payment\InvoiceReminder;
use App\Services\Payment\InvoiceCalculator;
use App\Services\Payment\InvoiceNumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       LIST
       ═══════════════════════════════════════════════════════ */

    public function getInvoices(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $query = $company->invoices()
                ->with(['customer:id,public_id,name,email'])
                ->search($request->get('search'));

            if ($request->filled('mode')) $query->where('mode', $request->mode);
            if ($request->filled('status')) {
                if ($request->status === 'overdue') {
                    $query->overdue();
                } else {
                    $query->where('status', $request->status);
                }
            }

            $invoices = $query->orderByDesc('created_at')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'current_page' => $invoices->currentPage(),
                'data' => collect($invoices->items())->map(fn ($i) => $this->formatInvoice($i))->toArray(),
                'from' => $invoices->firstItem(),
                'last_page' => $invoices->lastPage(),
                'next_page_url' => $invoices->nextPageUrl(),
                'prev_page_url' => $invoices->previousPageUrl(),
                'to' => $invoices->lastItem(),
                'total' => $invoices->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getInvoice($id)
    {
        try {
            $invoice = Invoice::with([
                'items.taxRate:id,public_id,display_name,percentage',
                'customer:id,public_id,name,email,phone,default_address_id',
                'customer.defaultAddress',
                'discount:id,public_id,code,type,percent_off,amount_off,currency',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatInvoiceDetail($invoice),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }
    }

    /* ═══════════════════════════════════════════════════════
       CREATE / UPDATE
       ═══════════════════════════════════════════════════════ */

    public function storeInvoice(Request $request, $companyId)
    {
        $data = $this->validatedInvoice($request);

        try {
            $company = Company::findOrFail($companyId);

            $invoice = DB::transaction(function () use ($company, $data) {
                $discountAmount = $this->resolveDiscountAmount($data, $company);

                $totals = InvoiceCalculator::compute($data['items'], $discountAmount);

                $invoice = $company->invoices()->create([
                    'customer_id' => $data['customer_id'] ?? null,
                    'created_by_id' => auth()->id(),
                    'mode' => $data['mode'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_email' => $data['customer_email'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'customer_address' => $data['customer_address'] ?? null,
                    'currency' => $data['currency'],
                    'discount_id' => $data['discount_id'] ?? null,
                    'discount_total' => $totals['discount_total'],
                    'subtotal' => $totals['subtotal'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                    'amount_due' => $totals['total'],
                    'status' => 'draft',
                    'collection_method' => $data['collection_method'] ?? 'send_invoice',
                    'allow_partial_payment' => $data['allow_partial_payment'] ?? false,
                    'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                    'due_date' => $data['due_date'] ?? null,
                    'payment_terms_days' => $data['payment_terms_days'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'internal_notes' => $data['internal_notes'] ?? null,
                    'auto_reminders_enabled' => $data['auto_reminders_enabled'] ?? true,
                ]);

                foreach ($totals['items'] as $item) {
                    $invoice->items()->create(array_merge($item, ['currency' => $data['currency']]));
                }

                return $invoice;
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice created as draft.',
                'data' => $this->formatInvoiceDetail($invoice->fresh(['items', 'customer'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateInvoice(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if (!$invoice->is_editable) {
            return response()->json([
                'success' => false,
                'message' => "Cannot edit a {$invoice->status} invoice.",
            ], 422);
        }

        $data = $this->validatedInvoice($request);

        try {
            DB::transaction(function () use ($invoice, $data) {
                $discountAmount = $this->resolveDiscountAmount($data, $invoice->company);
                $totals = InvoiceCalculator::compute($data['items'], $discountAmount);

                $invoice->update([
                    'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                    'customer_name' => $data['customer_name'] ?? $invoice->customer_name,
                    'customer_email' => $data['customer_email'] ?? $invoice->customer_email,
                    'customer_phone' => $data['customer_phone'] ?? $invoice->customer_phone,
                    'customer_address' => $data['customer_address'] ?? $invoice->customer_address,
                    'currency' => $data['currency'],
                    'discount_id' => $data['discount_id'] ?? null,
                    'subtotal' => $totals['subtotal'],
                    'discount_total' => $totals['discount_total'],
                    'tax_total' => $totals['tax_total'],
                    'total' => $totals['total'],
                    'amount_due' => max($totals['total'] - $invoice->amount_paid, 0),
                    'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                    'due_date' => $data['due_date'] ?? null,
                    'payment_terms_days' => $data['payment_terms_days'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'internal_notes' => $data['internal_notes'] ?? null,
                ]);

                // Replace line items
                $invoice->items()->delete();
                foreach ($totals['items'] as $item) {
                    $invoice->items()->create(array_merge($item, ['currency' => $data['currency']]));
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated.',
                'data' => $this->formatInvoiceDetail($invoice->fresh(['items', 'customer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteInvoice($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if (!in_array($invoice->status, ['draft', 'void'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft or void invoices can be deleted. Void it first.',
                ], 422);
            }

            $invoice->delete();
            return response()->json(['success' => true, 'message' => 'Invoice deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       LIFECYCLE
       ═══════════════════════════════════════════════════════ */

    public function finalizeInvoice($id)
    {
        try {
            $invoice = Invoice::with('items')->findOrFail($id);

            if ($invoice->status !== 'draft') {
                return response()->json(['success' => false, 'message' => 'Invoice is already finalized.'], 422);
            }

            if ($invoice->items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Cannot finalize an invoice with no line items.'], 422);
            }

            DB::transaction(function () use ($invoice) {
                $number = InvoiceNumberGenerator::next($invoice->company_id, $invoice->mode);

                $invoice->update([
                    'number' => $number,
                    'status' => 'open',
                    'finalized_at' => now(),
                    'hosted_url' => config('app.url') . '/pay/invoice/' . $invoice->public_id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice finalized as ' . $invoice->fresh()->number,
                'data' => $this->formatInvoiceDetail($invoice->fresh(['items', 'customer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendInvoice($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status === 'draft') {
                return response()->json(['success' => false, 'message' => 'Finalize the invoice before sending.'], 422);
            }

            // Stub: dispatch a job that sends the email.
            // dispatch(new \App\Jobs\SendInvoiceEmail($invoice));

            $invoice->update(['sent_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Invoice sent to {$invoice->customer_email}.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send'], 500);
        }
    }

    public function voidInvoice(Request $request, $id)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status === 'paid') {
                return response()->json(['success' => false, 'message' => 'Cannot void a paid invoice.'], 422);
            }
            if ($invoice->status === 'void') {
                return response()->json(['success' => false, 'message' => 'Invoice is already void.'], 422);
            }

            $invoice->update([
                'status' => 'void',
                'voided_at' => now(),
                'internal_notes' => trim(($invoice->internal_notes ?? '') . "\n[VOID] " . ($request->reason ?: 'No reason given')),
            ]);

            $invoice->reminders()->where('status', 'scheduled')->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice voided.',
                'data' => $this->formatInvoiceDetail($invoice->fresh(['items'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to void'], 500);
        }
    }

    public function markPaid(Request $request, $id)
    {
        $request->validate([
            'amount' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $invoice = Invoice::findOrFail($id);

            if (in_array($invoice->status, ['paid', 'void'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot mark a {$invoice->status} invoice as paid.",
                ], 422);
            }

            $amount = (int) ($request->amount ?? $invoice->amount_due);
            $amount = min($amount, $invoice->amount_due);

            $newPaid = $invoice->amount_paid + $amount;
            $newDue = max($invoice->total - $newPaid, 0);
            $status = $newDue === 0 ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newPaid,
                'amount_due' => $newDue,
                'status' => $status,
                'paid_at' => $status === 'paid' ? now() : $invoice->paid_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => $status === 'paid' ? 'Invoice marked as paid.' : 'Partial payment recorded.',
                'data' => $this->formatInvoiceDetail($invoice->fresh(['items'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to record payment'], 500);
        }
    }

    public function duplicateInvoice($id)
    {
        try {
            $source = Invoice::with('items')->findOrFail($id);

            $copy = DB::transaction(function () use ($source) {
                $copy = $source->company->invoices()->create([
                    'customer_id' => $source->customer_id,
                    'created_by_id' => auth()->id(),
                    'mode' => $source->mode,
                    'customer_name' => $source->customer_name,
                    'customer_email' => $source->customer_email,
                    'customer_phone' => $source->customer_phone,
                    'customer_address' => $source->customer_address,
                    'currency' => $source->currency,
                    'discount_id' => $source->discount_id,
                    'subtotal' => $source->subtotal,
                    'discount_total' => $source->discount_total,
                    'tax_total' => $source->tax_total,
                    'total' => $source->total,
                    'amount_due' => $source->total,
                    'status' => 'draft',
                    'collection_method' => $source->collection_method,
                    'allow_partial_payment' => $source->allow_partial_payment,
                    'issue_date' => now()->toDateString(),
                    'due_date' => $source->due_date,
                    'payment_terms_days' => $source->payment_terms_days,
                    'notes' => $source->notes,
                    'terms' => $source->terms,
                ]);

                foreach ($source->items as $item) {
                    $copy->items()->create($item->only([
                        'product_id', 'price_id', 'name', 'description',
                        'quantity', 'unit_label', 'unit_amount', 'currency',
                        'discount_amount', 'tax_rate_id', 'tax_percentage', 'tax_amount',
                        'subtotal', 'total', 'sort_order',
                    ]));
                }

                return $copy;
            });

            return response()->json([
                'success' => true,
                'message' => 'Invoice duplicated as a new draft.',
                'data' => $this->formatInvoice($copy),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to duplicate'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validatedInvoice(Request $request): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'currency' => ['required', 'string', 'size:3'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'customer_address' => ['nullable', 'array'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'collection_method' => ['nullable', Rule::in(['send_invoice', 'charge_automatically'])],
            'allow_partial_payment' => ['boolean'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'auto_reminders_enabled' => ['boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.price_id' => ['nullable', 'integer', 'exists:prices,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_label' => ['nullable', 'string', 'max:32'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
            'items.*.currency' => ['nullable', 'string', 'size:3'],
            'items.*.tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'items.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    protected function resolveDiscountAmount(array $data, Company $company): int
    {
        if (empty($data['discount_id'])) return 0;

        $discount = Discount::find($data['discount_id']);
        if (!$discount || !$discount->is_valid) return 0;

        // Compute subtotal from items for percentage calculations
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += (int) round((float) $item['quantity'] * (int) $item['unit_amount']);
        }

        return $discount->computeDiscount($subtotal);
    }

    protected function formatInvoice(Invoice $i): array
    {
        return [
            'id' => $i->id,
            'uuid' => $i->uuid,
            'public_id' => $i->public_id,
            'number' => $i->number,
            'company_id' => $i->company_id,
            'mode' => $i->mode,
            'mode_badge' => $i->mode_badge,
            'currency' => $i->currency,
            'subtotal' => $i->subtotal,
            'discount_total' => $i->discount_total,
            'tax_total' => $i->tax_total,
            'total' => $i->total,
            'amount_paid' => $i->amount_paid,
            'amount_due' => $i->amount_due,
            'total_display' => $this->money($i->total, $i->currency),
            'amount_due_display' => $this->money($i->amount_due, $i->currency),
            'customer' => $i->customer ? [
                'id' => $i->customer->id,
                'public_id' => $i->customer->public_id,
                'name' => $i->customer->name,
                'email' => $i->customer->email,
            ] : null,
            'customer_name' => $i->customer_name,
            'customer_email' => $i->customer_email,
            'status' => $i->status,
            'status_badge' => $i->status_badge,
            'is_overdue' => $i->is_overdue,
            'is_editable' => $i->is_editable,
            'issue_date' => $i->issue_date?->toDateString(),
            'due_date' => $i->due_date?->toDateString(),
            'sent_at' => $i->sent_at?->format('M d, Y H:i'),
            'paid_at' => $i->paid_at?->format('M d, Y H:i'),
            'created_at' => $i->created_at?->format('M d, Y'),
        ];
    }

    protected function formatInvoiceDetail(Invoice $i): array
    {
        return array_merge($this->formatInvoice($i), [
            'customer_address' => $i->customer_address,
            'notes' => $i->notes,
            'terms' => $i->terms,
            'internal_notes' => $i->internal_notes,
            'collection_method' => $i->collection_method,
            'allow_partial_payment' => (bool) $i->allow_partial_payment,
            'auto_reminders_enabled' => (bool) $i->auto_reminders_enabled,
            'finalized_at' => $i->finalized_at?->format('M d, Y H:i'),
            'voided_at' => $i->voided_at?->format('M d, Y H:i'),
            'hosted_url' => $i->hosted_url,
            'discount' => $i->discount ? [
                'id' => $i->discount->id,
                'code' => $i->discount->code,
                'label' => $i->discount->label,
            ] : null,
            'items' => $i->items->map(fn ($item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'product_id' => $item->product_id,
                'price_id' => $item->price_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_label' => $item->unit_label,
                'unit_amount' => $item->unit_amount,
                'unit_amount_display' => $this->money($item->unit_amount, $item->currency),
                'currency' => $item->currency,
                'discount_amount' => $item->discount_amount,
                'tax_rate_id' => $item->tax_rate_id,
                'tax_percentage' => (float) $item->tax_percentage,
                'tax_amount' => $item->tax_amount,
                'subtotal' => $item->subtotal,
                'subtotal_display' => $this->money($item->subtotal, $item->currency),
                'total' => $item->total,
                'total_display' => $this->money($item->total, $item->currency),
            ])->toArray(),
        ]);
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
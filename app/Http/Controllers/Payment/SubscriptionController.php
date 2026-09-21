<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\Subscription;
use App\Services\Payment\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       LIST
       ═══════════════════════════════════════════════════════ */

    public function getSubscriptions(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $query = $company->subscriptions()
                ->with(['customer:id,public_id,name,email']);

            if ($request->filled('search')) {
                $query->search($request->search);
            }
            if ($request->filled('mode')) {
                $query->where('mode', $request->mode);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $subs = $query->orderByDesc('created_at')->get();

            return response()->json([
                'success' => true,
                'data' => $subs->map(fn ($s) => $this->format($s))->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getStats($companyId)
    {
        try {
            $company = Company::findOrFail($companyId);
            $base = $company->subscriptions();

            return response()->json([
                'total'      => (clone $base)->count(),
                'active'     => (clone $base)->where('status', 'active')->count(),
                'trialing'   => (clone $base)->where('status', 'trialing')->count(),
                'past_due'   => (clone $base)->where('status', 'past_due')->count(),
                'paused'     => (clone $base)->where('status', 'paused')->count(),
                'cancelled'  => (clone $base)->where('status', 'cancelled')->count(),
                'mrr_minor'  => (int) (clone $base)->where('status', 'active')->sum('lifetime_amount'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getSubscription($id)
    {
        try {
            $sub = Subscription::with(['items.taxRate', 'customer', 'discount', 'paymentMethod'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($sub), [
                    'metadata' => $sub->metadata,
                    'items' => $sub->items->map(fn ($i) => [
                        'id' => $i->id,
                        'uuid' => $i->uuid,
                        'product_id' => $i->product_id,
                        'price_id' => $i->price_id,
                        'name' => $i->name,
                        'unit_amount' => $i->unit_amount,
                        'currency' => $i->currency,
                        'quantity' => $i->quantity,
                        'tax_rate_id' => $i->tax_rate_id,
                    ])->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        }
    }

    /* ═══════════════════════════════════════════════════════
       CREATE / UPDATE
       ═══════════════════════════════════════════════════════ */

    public function storeSubscription(Request $request, $companyId)
    {
        $data = $this->validated($request);

        try {
            $company = Company::findOrFail($companyId);

            $sub = \DB::transaction(function () use ($company, $data) {
                $interval = $data['billing_interval'];
                $count = $data['billing_interval_count'] ?? 1;
                $start = now();

                $sub = $company->subscriptions()->create([
                    'customer_id' => $data['customer_id'],
                    'payment_method_id' => $data['payment_method_id'] ?? null,
                    'mode' => $data['mode'],
                    'description' => $data['description'] ?? null,
                    'currency' => $data['currency'],
                    'status' => !empty($data['trial_period_days']) && $data['trial_period_days'] > 0 ? 'trialing' : 'active',
                    'collection_method' => $data['collection_method'] ?? 'charge_automatically',
                    'billing_interval' => $interval,
                    'billing_interval_count' => $count,
                    'current_period_start' => $start,
                    'current_period_end' => $this->addInterval($start, $interval, $count),
                    'trial_start' => !empty($data['trial_period_days']) ? $start : null,
                    'trial_end' => !empty($data['trial_period_days']) ? $start->copy()->addDays($data['trial_period_days']) : null,
                    'started_at' => $start,
                    'next_billing_at' => $this->addInterval($start, $interval, $count),
                    'discount_id' => $data['discount_id'] ?? null,
                ]);

                foreach ($data['items'] as $item) {
                    $sub->items()->create($item);
                }

                return $sub;
            });

            return response()->json([
                'success' => true,
                'message' => 'Subscription created.',
                'data' => $this->format($sub->fresh(['customer'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateSubscription(Request $request, $id)
    {
        try {
            $sub = Subscription::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
        }

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $sub->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated.',
                'data' => $this->format($sub->fresh(['customer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       LIFECYCLE
       ═══════════════════════════════════════════════════════ */

    public function cancelSubscription(Request $request, $id)
    {
        $request->validate([
            'at_period_end' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $sub = Subscription::findOrFail($id);

            if (in_array($sub->status, ['cancelled', 'expired'])) {
                return response()->json(['success' => false, 'message' => 'Already cancelled.'], 422);
            }

            if ($request->boolean('at_period_end')) {
                SubscriptionBillingService::scheduleCancellation($sub, $request->reason);
                $msg = 'Subscription will cancel at the end of the current period.';
            } else {
                SubscriptionBillingService::cancelImmediately($sub, $request->reason);
                $msg = 'Subscription cancelled immediately.';
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $this->format($sub->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to cancel'], 500);
        }
    }

    public function resumeSubscription($id)
    {
        try {
            $sub = Subscription::findOrFail($id);

            if (!$sub->cancel_at_period_end && $sub->status !== 'paused') {
                return response()->json(['success' => false, 'message' => 'Nothing to resume.'], 422);
            }

            SubscriptionBillingService::resume($sub);

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed.',
                'data' => $this->format($sub->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to resume'], 500);
        }
    }

    public function pauseSubscription($id)
    {
        try {
            $sub = Subscription::findOrFail($id);

            if (!in_array($sub->status, ['active', 'trialing'])) {
                return response()->json(['success' => false, 'message' => 'Only active subscriptions can be paused.'], 422);
            }

            $sub->update(['status' => 'paused', 'paused_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription paused.',
                'data' => $this->format($sub->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to pause'], 500);
        }
    }

    public function billNow($id)
    {
        try {
            $sub = Subscription::with('items')->findOrFail($id);

            if ($sub->items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Subscription has no items.'], 422);
            }

            $invoice = SubscriptionBillingService::bill($sub);

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Could not generate invoice.'], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice generated.',
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to bill'], 500);
        }
    }

    public function deleteSubscription($id)
    {
        try {
            $sub = Subscription::findOrFail($id);

            if (!in_array($sub->status, ['cancelled', 'expired', 'incomplete'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cancel the subscription before deleting it.',
                ], 422);
            }

            $sub->delete();

            return response()->json(['success' => true, 'message' => 'Subscription deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validated(Request $request): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'collection_method' => ['required', Rule::in(['charge_automatically', 'send_invoice'])],
            'billing_interval' => ['required', Rule::in(['day', 'week', 'month', 'year'])],
            'billing_interval_count' => ['required', 'integer', 'min:1', 'max:365'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.price_id' => ['nullable', 'integer', 'exists:prices,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit_amount' => ['required', 'integer', 'min:0'],
            'items.*.currency' => ['required', 'string', 'size:3'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
        ]);
    }

    protected function addInterval($from, string $interval, int $count)
    {
        $from = \Carbon\Carbon::parse($from);
        return match ($interval) {
            'day'   => $from->copy()->addDays($count),
            'week'  => $from->copy()->addWeeks($count),
            'month' => $from->copy()->addMonths($count),
            'year'  => $from->copy()->addYears($count),
            default => $from->copy()->addMonth(),
        };
    }

    protected function format(Subscription $s): array
    {
        return [
            'id' => $s->id,
            'uuid' => $s->uuid,
            'public_id' => $s->public_id,
            'company_id' => $s->company_id,
            'mode' => $s->mode,
            'mode_badge' => $s->mode_badge,
            'description' => $s->description,
            'currency' => $s->currency,
            'status' => $s->status,
            'status_badge' => $s->status_badge,
            'is_cancelling' => $s->is_cancelling,
            'billing_interval' => $s->billing_interval,
            'billing_interval_count' => $s->billing_interval_count,
            'interval_label' => $s->interval_label,
            'current_period_start' => $s->current_period_start?->format('M d, Y'),
            'current_period_end' => $s->current_period_end?->format('M d, Y'),
            'next_billing_at' => $s->next_billing_at?->format('M d, Y'),
            'trial_end' => $s->trial_end?->format('M d, Y'),
            'cancelled_at' => $s->cancelled_at?->format('M d, Y'),
            'cancel_at' => $s->cancel_at?->format('M d, Y'),
            'cancel_at_period_end' => (bool) $s->cancel_at_period_end,
            'customer' => $s->customer ? [
                'id' => $s->customer->id,
                'public_id' => $s->customer->public_id,
                'name' => $s->customer->name,
                'email' => $s->customer->email,
            ] : null,
            'invoices_generated' => $s->invoices_generated,
            'lifetime_amount' => $s->lifetime_amount,
            'failed_payment_attempts' => $s->failed_payment_attempts,
            'created_at' => $s->created_at?->format('M d, Y'),
        ];
    }
}
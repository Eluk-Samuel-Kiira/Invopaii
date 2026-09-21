<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Payment\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       CUSTOMERS
       ═══════════════════════════════════════════════════════ */

    public function getCustomers(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $search = $request->get('search', '');
            $mode = $request->get('mode');
            $status = $request->get('status');

            $query = $company->customers()
                ->with(['defaultPaymentMethod:id,public_id,type,card_brand,card_last_four,mobile_network,msisdn_last_four'])
                ->withCount(['addresses', 'paymentMethods'])
                ->search($search);

            if ($mode) $query->where('mode', $mode);
            if ($status === 'blocked') $query->where('is_blocked', true);
            if ($status === 'active') $query->where('is_blocked', false);

            $customers = $query->orderByDesc('created_at')
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'current_page' => $customers->currentPage(),
                'data' => collect($customers->items())->map(fn ($c) => $this->formatCustomer($c))->toArray(),
                'from' => $customers->firstItem(),
                'last_page' => $customers->lastPage(),
                'next_page_url' => $customers->nextPageUrl(),
                'prev_page_url' => $customers->previousPageUrl(),
                'to' => $customers->lastItem(),
                'total' => $customers->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getCustomer($id)
    {
        try {
            $customer = Customer::with([
                'addresses',
                'paymentMethods',
                'defaultPaymentMethod',
                'defaultAddress',
            ])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $this->formatCustomerDetail($customer)]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }
    }

    public function storeCustomer(Request $request, $companyId)
    {
        $data = $this->validatedCustomer($request);

        try {
            $company = Company::findOrFail($companyId);

            // Unique email per (company, mode)
            if (!empty($data['email'])) {
                $exists = $company->customers()
                    ->where('mode', $data['mode'])
                    ->where('email', $data['email'])
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A customer with this email already exists in this mode.',
                    ], 422);
                }
            }

            $customer = $company->customers()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Customer created.',
                'data' => $this->formatCustomerDetail($customer->fresh()),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateCustomer(Request $request, $id)
    {
        try {
            $customer = Customer::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $data = $this->validatedCustomer($request, $customer->id);

        try {
            // Re-check email uniqueness if changed
            if (!empty($data['email']) && $data['email'] !== $customer->email) {
                $exists = Customer::where('company_id', $customer->company_id)
                    ->where('mode', $customer->mode)
                    ->where('email', $data['email'])
                    ->where('id', '!=', $customer->id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Another customer with this email already exists.',
                    ], 422);
                }
            }

            $customer->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated.',
                'data' => $this->formatCustomerDetail($customer->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteCustomer($id)
    {
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();

            return response()->json(['success' => true, 'message' => 'Customer deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    public function toggleBlock(Request $request, $id)
    {
        $request->validate(['blocked_reason' => ['nullable', 'string', 'max:255']]);

        try {
            $customer = Customer::findOrFail($id);
            $customer->is_blocked = !$customer->is_blocked;
            $customer->blocked_reason = $customer->is_blocked ? ($request->blocked_reason ?? 'Blocked by admin') : null;
            $customer->save();

            return response()->json([
                'success' => true,
                'message' => $customer->is_blocked ? 'Customer blocked.' : 'Customer unblocked.',
                'data' => $this->formatCustomerDetail($customer->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle block'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       ADDRESSES
       ═══════════════════════════════════════════════════════ */

    public function storeAddress(Request $request, $customerId)
    {
        $data = $this->validatedAddress($request);

        try {
            $customer = Customer::findOrFail($customerId);
            $address = $customer->addresses()->create($data);

            // If marked default, set as customer default
            if ($data['is_default'] ?? false) {
                $customer->update(['default_address_id' => $address->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address added.',
                'data' => $address->fresh(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateAddress(Request $request, $id)
    {
        try {
            $address = CustomerAddress::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Address not found'], 404);
        }

        $data = $this->validatedAddress($request);

        try {
            $address->update($data);

            if (($data['is_default'] ?? false) && $address->type === 'billing') {
                $address->customer->update(['default_address_id' => $address->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address updated.',
                'data' => $address->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteAddress($id)
    {
        try {
            $address = CustomerAddress::findOrFail($id);
            $address->delete();

            return response()->json(['success' => true, 'message' => 'Address removed.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       PAYMENT METHODS
       ═══════════════════════════════════════════════════════ */

    public function getPaymentMethods(Request $request, $customerId)
    {
        try {
            $customer = Customer::findOrFail($customerId);

            $methods = $customer->paymentMethods()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($pm) => $this->formatPaymentMethod($pm));

            return response()->json(['success' => true, 'data' => $methods]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }
    }

    public function setDefaultPaymentMethod($id)
    {
        try {
            $pm = PaymentMethod::findOrFail($id);
            $pm->update(['is_default' => true]);

            if ($pm->customer_id) {
                $pm->customer->update(['default_payment_method_id' => $pm->id]);
            }

            return response()->json(['success' => true, 'message' => 'Default payment method updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to set default'], 500);
        }
    }

    public function revokePaymentMethod($id)
    {
        try {
            $pm = PaymentMethod::findOrFail($id);
            $pm->update(['status' => 'revoked']);

            if ($pm->customer && $pm->customer->default_payment_method_id === $pm->id) {
                $pm->customer->update(['default_payment_method_id' => null]);
            }

            return response()->json(['success' => true, 'message' => 'Payment method revoked.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to revoke'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validatedCustomer(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'preferred_currency' => ['nullable', 'string', 'size:3'],
            'preferred_locale' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'tax_exempt' => ['boolean'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'shipping_address' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);
    }

    protected function validatedAddress(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['billing', 'shipping'])],
            'name' => ['nullable', 'string', 'max:255'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_default' => ['boolean'],
        ]);
    }

    protected function formatCustomer(Customer $c): array
    {
        return [
            'id' => $c->id,
            'uuid' => $c->uuid,
            'public_id' => $c->public_id,
            'company_id' => $c->company_id,
            'mode' => $c->mode,
            'mode_badge' => $c->mode === 'live' ? ['label' => 'Live', 'tone' => 'danger'] : ['label' => 'Test', 'tone' => 'info'],
            'name' => $c->name,
            'display_name' => $c->display_name,
            'initials' => $c->initials,
            'email' => $c->email,
            'phone' => $c->phone,
            'reference' => $c->reference,
            'country_code' => $c->country_code,
            'preferred_currency' => $c->preferred_currency,
            'lifetime_value' => $c->lifetime_value,
            'successful_payments_count' => $c->successful_payments_count,
            'disputed_payments_count' => $c->disputed_payments_count,
            'last_paid_at' => $c->last_paid_at?->format('M d, Y'),
            'is_blocked' => $c->is_blocked,
            'blocked_reason' => $c->blocked_reason,
            'status_badge' => $c->status_badge,
            'addresses_count' => $c->addresses_count ?? null,
            'payment_methods_count' => $c->payment_methods_count ?? null,
            'default_payment_method' => $c->defaultPaymentMethod
                ? $this->formatPaymentMethod($c->defaultPaymentMethod)
                : null,
            'created_at' => $c->created_at?->format('M d, Y'),
        ];
    }

    protected function formatCustomerDetail(Customer $c): array
    {
        return array_merge($this->formatCustomer($c), [
            'description' => $c->description,
            'preferred_locale' => $c->preferred_locale,
            'timezone' => $c->timezone,
            'tax_exempt' => (bool) $c->tax_exempt,
            'tax_id' => $c->tax_id,
            'first_paid_at' => $c->first_paid_at?->format('M d, Y H:i'),
            'shipping_address' => $c->shipping_address,
            'metadata' => $c->metadata,
            'addresses' => $c->addresses->map(fn ($a) => [
                'id' => $a->id,
                'uuid' => $a->uuid,
                'type' => $a->type,
                'name' => $a->name,
                'line1' => $a->line1,
                'line2' => $a->line2,
                'city' => $a->city,
                'state' => $a->state,
                'postal_code' => $a->postal_code,
                'country_code' => $a->country_code,
                'phone' => $a->phone,
                'is_default' => (bool) $a->is_default,
                'one_line' => $a->one_line,
            ])->toArray(),
            'payment_methods' => $c->paymentMethods->map(fn ($pm) => $this->formatPaymentMethod($pm))->toArray(),
        ]);
    }

    protected function formatPaymentMethod(PaymentMethod $pm): array
    {
        return [
            'id' => $pm->id,
            'uuid' => $pm->uuid,
            'public_id' => $pm->public_id,
            'type' => $pm->type,
            'display_label' => $pm->display_label,
            'card_brand' => $pm->card_brand,
            'card_last_four' => $pm->card_last_four,
            'card_exp_month' => $pm->card_exp_month,
            'card_exp_year' => $pm->card_exp_year,
            'mobile_network' => $pm->mobile_network,
            'bank_name' => $pm->bank_name,
            'is_default' => (bool) $pm->is_default,
            'is_reusable' => (bool) $pm->is_reusable,
            'is_expired' => $pm->is_expired,
            'status' => $pm->status,
            'status_badge' => $pm->status_badge,
            'provider' => $pm->provider,
            'last_used_at' => $pm->last_used_at?->format('M d, Y'),
            'created_at' => $pm->created_at?->format('M d, Y'),
        ];
    }
}
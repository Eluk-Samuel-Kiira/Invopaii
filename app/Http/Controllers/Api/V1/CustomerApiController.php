<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerApiController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->attributes->get('company_id');

        $customers = Customer::where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get();

        return response()->json([
            'data' => $customers->map(fn ($c) => $this->format($c))->toArray(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $customer = Customer::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        return response()->json($this->format($customer));
    }

    public function store(Request $request)
    {
        $companyId = $request->attributes->get('company_id');
        $mode = $request->attributes->get('mode');

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (!empty($data['email'])) {
            $exists = Customer::where('company_id', $companyId)
                ->where('mode', $mode)
                ->where('email', $data['email'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => [
                        'type' => 'invalid_request_error',
                        'message' => 'A customer with this email already exists.',
                        'code' => 'customer_email_exists',
                    ],
                ], 422);
            }
        }

        $customer = Customer::create(array_merge($data, [
            'company_id' => $companyId,
            'mode' => $mode,
        ]));

        \App\Services\Webhook\EventDispatcher::dispatch(
            company: $customer->company,
            type: 'customer.created',
            data: [
                'id' => $customer->public_id,
                'object' => 'customer',
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'country_code' => $customer->country_code,
                'reference' => $customer->reference,
                'created' => $customer->created_at->toIso8601String(),
            ],
            resource: $customer,
            origin: 'api',
        );

        return response()->json($this->format($customer), 201);
    }

    public function update(Request $request, $id)
    {
        $companyId = $request->attributes->get('company_id');

        $customer = Customer::where('company_id', $companyId)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('public_id', $id);
            })
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'reference' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $customer->update($data);

        \App\Services\Webhook\EventDispatcher::dispatch(
            company: $customer->company,
            type: 'customer.updated',
            data: [
                'id' => $customer->public_id,
                'object' => 'customer',
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'country_code' => $customer->country_code,
                'reference' => $customer->reference,
                'updated' => $customer->updated_at->toIso8601String(),
            ],
            resource: $customer,
            origin: 'api',
        );

        return response()->json($this->format($customer->fresh()));
    }

    protected function format(Customer $customer): array
    {
        return [
            'id' => $customer->public_id,
            'object' => 'customer',
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'country_code' => $customer->country_code,
            'reference' => $customer->reference,
            'lifetime_value' => $customer->lifetime_value,
            'successful_payments_count' => $customer->successful_payments_count,
            'metadata' => $customer->metadata ?? new \stdClass(),
            'created' => $customer->created_at->toIso8601String(),
        ];
    }
}
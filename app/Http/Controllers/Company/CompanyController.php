<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Reference\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index()
    {
        return view('company.companies.index');
    }

    public function getCompanies(Request $request)
    {
        $search = $request->get('search', '');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 20);
        $status = $request->get('status');
        $kyb = $request->get('kyb_status');

        $companies = Company::query()
            ->with(['country:id,name,iso2,flag_emoji', 'owner:id,name,email'])
            ->search($search)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($kyb, fn ($q) => $q->where('kyb_status', $kyb))
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'current_page' => $companies->currentPage(),
            'data' => collect($companies->items())->map(fn ($c) => $this->format($c))->toArray(),
            'first_page_url' => $companies->url(1),
            'from' => $companies->firstItem(),
            'last_page' => $companies->lastPage(),
            'last_page_url' => $companies->url($companies->lastPage()),
            'next_page_url' => $companies->nextPageUrl(),
            'prev_page_url' => $companies->previousPageUrl(),
            'to' => $companies->lastItem(),
            'total' => $companies->total(),
            'per_page' => $perPage,
        ]);
    }

    public function getCompany($id)
    {
        try {
            $company = Company::with([
                'country:id,name,iso2,flag_emoji',
                'owner:id,name,email',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($company), [
                    'legal_name' => $company->legal_name,
                    'support_email' => $company->support_email,
                    'support_phone' => $company->support_phone,
                    'website' => $company->website,
                    'brand_color' => $company->brand_color,
                    'industry' => $company->industry,
                    'mcc' => $company->mcc,
                    'registration_number' => $company->registration_number,
                    'tax_identification_number' => $company->tax_identification_number,
                    'incorporated_on' => $company->incorporated_on?->toDateString(),
                    'address_line1' => $company->address_line1,
                    'address_line2' => $company->address_line2,
                    'city' => $company->city,
                    'state' => $company->state,
                    'postal_code' => $company->postal_code,
                    'settlement_currency' => $company->settlement_currency,
                    'timezone' => $company->timezone,
                    'statement_descriptor' => $company->statement_descriptor,
                    'payout_schedule' => $company->payout_schedule,
                    'payout_delay_days' => $company->payout_delay_days,
                    'reserve_percent' => (string) $company->reserve_percent,
                    'reserve_hold_days' => $company->reserve_hold_days,
                    'risk_level' => $company->risk_level,
                    'risk_score' => $company->risk_score,
                    'onboarding_step' => $company->onboarding_step,
                    'requirements_due' => $company->requirements_due ?? [],
                    'suspension_reason' => $company->suspension_reason,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeCompany(Request $request)
    {
        $data = $this->validated($request);

        try {
            return DB::transaction(function () use ($data) {
                $company = Company::create($data);

                // Attach the owner to the team pivot as 'owner'
                $company->users()->attach($company->owner_id, [
                    'role' => 'owner',
                    'can_access_live_mode' => true,
                    'status' => 'active',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Company created successfully',
                    'data' => $this->format($company->fresh(['country', 'owner'])),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create company: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateCompany(Request $request, $id)
    {
        try {
            $company = Company::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }

        $data = $this->validated($request, $company->id);

        try {
            $company->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $this->format($company->fresh(['country', 'owner'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCompany($id)
    {
        try {
            $company = Company::findOrFail($id);

            if ($company->status === 'active' && $company->live_mode_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deactivate live mode before deleting an active company.',
                ], 422);
            }

            $company->delete();

            return response()->json(['success' => true, 'message' => 'Company deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change lifecycle status. Enforces legal transitions.
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                'pending', 'in_review', 'active', 'restricted', 'suspended', 'rejected', 'closed',
            ])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $company = Company::findOrFail($id);
            $old = $company->status;
            $new = $request->status;

            // Guard: can't jump straight from pending to active unless KYB verified
            if ($new === 'active' && $company->kyb_status !== 'verified') {
                return response()->json([
                    'success' => false,
                    'message' => 'Company must have KYB verified before activating.',
                ], 422);
            }

            $company->status = $new;

            if ($new === 'active') {
                $company->activated_at = now();
                $company->charges_enabled = true;
                $company->payouts_enabled = $company->settlement_currency ? true : false;
            }

            if (in_array($new, ['suspended', 'rejected', 'closed'])) {
                $company->live_mode_enabled = false;
                $company->charges_enabled = false;
                $company->payouts_enabled = false;
            }

            if ($new === 'suspended') {
                $company->suspended_at = now();
                $company->suspension_reason = $request->reason;
            }

            $company->save();

            return response()->json([
                'success' => true,
                'message' => "Company status changed from {$old} to {$new}.",
                'data' => $this->format($company->fresh(['country', 'owner'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle one of: live_mode_enabled, charges_enabled, payouts_enabled.
     */
    public function toggleFlag(Request $request, $id)
    {
        $request->validate([
            'field' => ['required', Rule::in(['live_mode_enabled', 'charges_enabled', 'payouts_enabled'])],
        ]);

        try {
            $company = Company::findOrFail($id);
            $field = $request->field;

            if ($company->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active companies can toggle live flags.',
                ], 422);
            }

            $company->$field = !$company->$field;

            // Cascade: turning off charges also turns off payouts and live mode
            if ($field === 'charges_enabled' && !$company->charges_enabled) {
                $company->payouts_enabled = false;
                $company->live_mode_enabled = false;
            }

            // Payouts require charges
            if ($field === 'payouts_enabled' && $company->payouts_enabled) {
                $company->charges_enabled = true;
            }

            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Flag updated successfully',
                'data' => $this->format($company),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle flag: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Small endpoint to populate select dropdowns in the form.
     */
    public function getFormOptions()
    {
        return response()->json([
            'countries' => Country::where('is_supported', true)
                ->orderBy('name')
                ->get(['id', 'name', 'iso2', 'default_currency', 'flag_emoji'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'label' => ($c->flag_emoji ? $c->flag_emoji . ' ' : '') . $c->name,
                    'currency' => $c->default_currency,
                ]),
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn ($u) => ['id' => $u->id, 'label' => "{$u->name} — {$u->email}"]),
            'business_types' => ['individual', 'company', 'ngo', 'government'],
            'payout_schedules' => ['manual', 'daily', 'weekly', 'monthly'],
            'risk_levels' => ['low', 'standard', 'elevated', 'high'],
        ]);
    }

    /* ---------- Helpers ---------- */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $slugRule = Rule::unique('companies', 'slug');
        if ($ignoreId) $slugRule->ignore($ignoreId);

        $validated = $request->validate([
            // Identity
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', $slugRule],
            'email' => ['nullable', 'email', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'brand_color' => ['nullable', 'string', 'max:9'],

            // Registration
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'business_type' => ['required', Rule::in(['individual', 'company', 'ngo', 'government'])],
            'industry' => ['nullable', 'string', 'max:64'],
            'mcc' => ['nullable', 'string', 'max:8'],
            'registration_number' => ['nullable', 'string', 'max:64'],
            'tax_identification_number' => ['nullable', 'string', 'max:64'],
            'incorporated_on' => ['nullable', 'date'],

            // Address
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'postal_code' => ['nullable', 'string', 'max:32'],

            // Money
            'default_currency' => ['required', 'string', 'size:3'],
            'settlement_currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'statement_descriptor' => ['nullable', 'string', 'max:22'],

            // Risk & commercials
            'risk_level' => ['required', Rule::in(['low', 'standard', 'elevated', 'high'])],
            'payout_schedule' => ['required', Rule::in(['manual', 'daily', 'weekly', 'monthly'])],
            'payout_delay_days' => ['required', 'integer', 'min:0', 'max:30'],
            'reserve_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reserve_hold_days' => ['required', 'integer', 'min:0', 'max:365'],

            // Owner
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $validated['default_currency'] = strtoupper($validated['default_currency']);
        if (!empty($validated['settlement_currency'])) {
            $validated['settlement_currency'] = strtoupper($validated['settlement_currency']);
        }

        // Auto-slug if blank
        if (empty($validated['slug'])) {
            $base = Str::slug($validated['name']);
            $slug = $base;
            $i = 2;
            while (Company::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
                $slug = "{$base}-{$i}";
                $i++;
            }
            $validated['slug'] = $slug;
        }

        return $validated;
    }

    protected function format(Company $c): array
    {
        return [
            'id' => $c->id,
            'uuid' => $c->uuid,
            'public_id' => $c->public_id,
            'name' => $c->name,
            'legal_name' => $c->legal_name,
            'slug' => $c->slug,
            'email' => $c->email,
            'website' => $c->website,
            'brand_color' => $c->brand_color,

            'country_id' => $c->country_id,
            'country' => $c->country ? [
                'id' => $c->country->id,
                'name' => $c->country->name,
                'iso2' => $c->country->iso2,
                'flag_emoji' => $c->country->flag_emoji,
            ] : null,

            'business_type' => $c->business_type,
            'industry' => $c->industry,
            'mcc' => $c->mcc,

            'default_currency' => $c->default_currency,
            'settlement_currency' => $c->settlement_currency,

            'status' => $c->status,
            'status_badge' => $c->status_badge,
            'kyb_status' => $c->kyb_status,
            'kyb_badge' => $c->kyb_badge,

            'live_mode_enabled' => (bool) $c->live_mode_enabled,
            'charges_enabled' => (bool) $c->charges_enabled,
            'payouts_enabled' => (bool) $c->payouts_enabled,

            'risk_level' => $c->risk_level,
            'risk_score' => $c->risk_score,

            'owner' => $c->owner ? [
                'id' => $c->owner->id,
                'name' => $c->owner->name,
                'email' => $c->owner->email,
            ] : null,

            'activated_at' => $c->activated_at?->format('M d, Y H:i'),
            'suspended_at' => $c->suspended_at?->format('M d, Y H:i'),

            'created_at' => $c->created_at?->format('M d, Y'),
        ];
    }
}
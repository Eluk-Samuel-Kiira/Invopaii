<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\ProviderCredential;
use App\Models\Payment\RoutingRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       PROVIDERS
       ═══════════════════════════════════════════════════════ */

    public function index()
    {
        return view('providers.index');
    }

    public function getProviders(Request $request)
    {
        $query = PaymentProvider::query()->search($request->get('search'));

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $providers = $query->orderBy('priority')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $providers->map(fn ($p) => $this->formatProvider($p))->toArray(),
        ]);
    }

    public function getStats()
    {
        return response()->json([
            'total'        => PaymentProvider::count(),
            'active'       => PaymentProvider::where('is_active', true)->count(),
            'mobile_money' => PaymentProvider::where('type', 'mobile_money')->count(),
            'aggregators'  => PaymentProvider::where('type', 'aggregator')->count(),
            'down'         => PaymentProvider::where('health_status', '!=', 'healthy')->count(),
            'rules'        => RoutingRule::where('is_active', true)->count(),
        ]);
    }

    public function getProvider($id)
    {
        try {
            $provider = PaymentProvider::with([
                'credentials.company:id,name,public_id',
                'credentials' => fn ($q) => $q->orderBy('company_id')->orderBy('mode'),
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->formatProvider($provider), [
                    'settings' => $provider->settings,
                    'webhook_path' => $provider->webhook_path,
                    'credentials' => $provider->credentials->map(fn ($c) => $this->formatCredential($c))->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }
    }

    public function storeProvider(Request $request)
    {
        $data = $this->validatedProvider($request);

        try {
            $provider = PaymentProvider::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Provider created.',
                'data' => $this->formatProvider($provider),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProvider(Request $request, $id)
    {
        try {
            $provider = PaymentProvider::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }

        $data = $this->validatedProvider($request, $provider->id);

        try {
            $provider->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Provider updated.',
                'data' => $this->formatProvider($provider->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleProvider($id)
    {
        try {
            $provider = PaymentProvider::findOrFail($id);
            $provider->update(['is_active' => !$provider->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Provider ' . ($provider->is_active ? 'enabled.' : 'disabled.'),
                'data' => $this->formatProvider($provider->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle'], 500);
        }
    }

    public function deleteProvider($id)
    {
        try {
            $provider = PaymentProvider::findOrFail($id);

            if ($provider->routingRules()->exists() || $provider->fallbackRules()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider is used by routing rules. Remove those first.',
                ], 422);
            }

            $provider->delete();

            return response()->json(['success' => true, 'message' => 'Provider deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       CREDENTIALS
       ═══════════════════════════════════════════════════════ */

    public function getCredentials(Request $request, $providerId)
    {
        try {
            $provider = PaymentProvider::findOrFail($providerId);

            $creds = $provider->credentials()
                ->with('company:id,name,public_id')
                ->orderBy('company_id')
                ->orderBy('mode')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $creds->map(fn ($c) => $this->formatCredential($c))->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Provider not found'], 404);
        }
    }

    public function storeCredential(Request $request, $providerId)
    {
        $data = $this->validatedCredential($request);

        try {
            $provider = PaymentProvider::findOrFail($providerId);

            // Prevent duplicate (provider, company, mode)
            $exists = $provider->credentials()
                ->where('company_id', $data['company_id'])
                ->where('mode', $data['mode'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credentials already exist for this scope and mode.',
                ], 422);
            }

            $data['payment_provider_id'] = $provider->id;
            $cred = ProviderCredential::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Credentials saved.',
                'data' => $this->formatCredential($cred->fresh(['company'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateCredential(Request $request, $id)
    {
        try {
            $cred = ProviderCredential::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Credential not found'], 404);
        }

        $data = $this->validatedCredential($request, true);

        try {
            // Never blank out a secret with an empty string
            foreach (['secret_key', 'webhook_secret', 'extra'] as $field) {
                if (array_key_exists($field, $data) && empty($data[$field])) {
                    unset($data[$field]);
                }
            }

            $cred->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Credentials updated.',
                'data' => $this->formatCredential($cred->fresh(['company'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteCredential($id)
    {
        try {
            $cred = ProviderCredential::findOrFail($id);
            $cred->delete();

            return response()->json(['success' => true, 'message' => 'Credentials deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       ROUTING RULES
       ═══════════════════════════════════════════════════════ */

    public function getRoutingRules(Request $request)
    {
        $query = RoutingRule::with([
            'provider:id,code,name,type',
            'fallbackProvider:id,code,name,type',
            'company:id,name,public_id',
        ]);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $rules = $query->orderByRaw('company_id IS NULL DESC, priority ASC, id ASC')->get();

        return response()->json([
            'success' => true,
            'data' => $rules->map(fn ($r) => $this->formatRule($r))->toArray(),
        ]);
    }

    public function storeRoutingRule(Request $request)
    {
        $data = $this->validatedRule($request);

        try {
            $rule = RoutingRule::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Routing rule created.',
                'data' => $this->formatRule($rule->fresh(['provider', 'fallbackProvider', 'company'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateRoutingRule(Request $request, $id)
    {
        try {
            $rule = RoutingRule::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $data = $this->validatedRule($request);

        try {
            $rule->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Routing rule updated.',
                'data' => $this->formatRule($rule->fresh(['provider', 'fallbackProvider', 'company'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleRoutingRule($id)
    {
        try {
            $rule = RoutingRule::findOrFail($id);
            $rule->update(['is_active' => !$rule->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Rule ' . ($rule->is_active ? 'enabled.' : 'disabled.'),
                'data' => $this->formatRule($rule->fresh(['provider', 'fallbackProvider'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle'], 500);
        }
    }

    public function deleteRoutingRule($id)
    {
        try {
            $rule = RoutingRule::findOrFail($id);
            $rule->delete();

            return response()->json(['success' => true, 'message' => 'Routing rule deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       FORM OPTIONS
       ═══════════════════════════════════════════════════════ */

    public function getFormOptions()
    {
        return response()->json([
            'types' => [
                ['value' => 'mobile_money', 'label' => 'Mobile Money'],
                ['value' => 'acquirer', 'label' => 'Acquirer'],
                ['value' => 'aggregator', 'label' => 'Aggregator'],
                ['value' => 'wallet', 'label' => 'Wallet'],
                ['value' => 'bank', 'label' => 'Bank'],
            ],
            'payment_methods' => [
                ['value' => 'card', 'label' => 'Card'],
                ['value' => 'mobile_money', 'label' => 'Mobile Money'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'ussd', 'label' => 'USSD'],
                ['value' => 'wallet', 'label' => 'Wallet'],
            ],
            'companies' => Company::orderBy('name')->get(['id', 'name', 'public_id'])
                ->map(fn ($c) => ['value' => $c->id, 'label' => "{$c->name} ({$c->public_id})"]),
            'countries' => \App\Models\Reference\Country::where('is_supported', true)
                ->orderBy('name')
                ->get(['iso2', 'name', 'flag_emoji'])
                ->map(fn ($c) => ['value' => $c->iso2, 'label' => ($c->flag_emoji ? $c->flag_emoji . ' ' : '') . $c->name]),
            'currencies' => \App\Models\Reference\Currency::where('is_active', true)
                ->orderBy('code')
                ->get(['code', 'name'])
                ->map(fn ($c) => ['value' => $c->code, 'label' => "{$c->code} — {$c->name}"]),
            'providers' => PaymentProvider::orderBy('name')->get(['id', 'code', 'name', 'type'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => "{$p->name} ({$p->code})"]),
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       VALIDATORS
       ═══════════════════════════════════════════════════════ */

    protected function validatedProvider(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('payment_providers', 'code');
        if ($ignoreId) $codeRule->ignore($ignoreId);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:48', 'regex:/^[a-z0-9_]+$/', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['mobile_money', 'acquirer', 'aggregator', 'wallet', 'bank'])],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'supported_countries' => ['nullable', 'array'],
            'supported_countries.*' => ['string', 'size:2'],
            'supported_currencies' => ['nullable', 'array'],
            'supported_currencies.*' => ['string', 'size:3'],
            'supported_methods' => ['nullable', 'array'],
            'supported_methods.*' => ['string', 'max:32'],
            'capabilities' => ['nullable', 'array'],
            'supports_test_mode' => ['boolean'],
            'is_active' => ['boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'webhook_path' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
        ]);

        $data['supports_test_mode'] = $data['supports_test_mode'] ?? true;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function validatedCredential(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'label' => ['nullable', 'string', 'max:255'],
            'public_key' => ['nullable', 'string', 'max:2000'],
            'merchant_account_id' => ['nullable', 'string', 'max:255'],
            'extra' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];

        if ($isUpdate) {
            $rules['secret_key'] = ['nullable', 'string', 'max:2000'];
            $rules['webhook_secret'] = ['nullable', 'string', 'max:2000'];
        } else {
            $rules['secret_key'] = ['required', 'string', 'max:2000'];
            $rules['webhook_secret'] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function validatedRule(Request $request): array
    {
        return $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'payment_provider_id' => ['required', 'integer', 'exists:payment_providers,id'],
            'fallback_provider_id' => ['nullable', 'integer', 'exists:payment_providers,id', 'different:payment_provider_id'],
            'mode' => ['nullable', Rule::in(['test', 'live'])],
            'name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::in(['card', 'mobile_money', 'bank_transfer', 'ussd', 'wallet'])],
            'currency' => ['nullable', 'string', 'size:3'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_amount' => ['nullable', 'integer', 'min:0', 'gte:min_amount'],
            'priority' => ['required', 'integer', 'min:1', 'max:1000'],
            'traffic_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       FORMATTERS
       ═══════════════════════════════════════════════════════ */

    protected function formatProvider(PaymentProvider $p): array
    {
        return [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'type' => $p->type,
            'type_label' => $p->type_label,
            'logo_url' => $p->logo_url,
            'supported_countries' => $p->supported_countries ?? [],
            'supported_currencies' => $p->supported_currencies ?? [],
            'supported_methods' => $p->supported_methods ?? [],
            'capabilities' => $p->capabilities ?? [],
            'supports_test_mode' => (bool) $p->supports_test_mode,
            'is_active' => (bool) $p->is_active,
            'status_badge' => $p->status_badge,
            'priority' => $p->priority,
            'success_rate' => $p->success_rate ? (float) $p->success_rate : null,
            'avg_latency_ms' => $p->avg_latency_ms,
            'health_status' => $p->health_status,
            'health_badge' => $p->health_badge,
            'health_checked_at' => $p->health_checked_at?->format('M d, Y H:i'),
            'webhook_path' => $p->webhook_path,
            'credentials_count' => $p->credentials()->count(),
            'routing_rules_count' => $p->routingRules()->count(),
            'created_at' => $p->created_at?->format('M d, Y'),
        ];
    }

    protected function formatCredential(ProviderCredential $c): array
    {
        return [
            'id' => $c->id,
            'payment_provider_id' => $c->payment_provider_id,
            'company_id' => $c->company_id,
            'company' => $c->company ? [
                'id' => $c->company->id,
                'name' => $c->company->name,
                'public_id' => $c->company->public_id,
            ] : null,
            'scope' => $c->company_id ? 'merchant' : 'platform',
            'mode' => $c->mode,
            'label' => $c->label,
            'masked_public_key' => $c->masked_public_key,
            'masked_secret' => $c->masked_secret,
            'masked_webhook_secret' => $c->masked_webhook_secret,
            'merchant_account_id' => $c->merchant_account_id,
            'has_public_key' => !empty($c->public_key),
            'has_secret_key' => !empty($c->secret_key),
            'has_webhook_secret' => !empty($c->webhook_secret),
            'is_active' => (bool) $c->is_active,
            'last_verified_at' => $c->last_verified_at?->format('M d, Y H:i'),
            'created_at' => $c->created_at?->format('M d, Y'),
        ];
    }

    protected function formatRule(RoutingRule $r): array
    {
        return [
            'id' => $r->id,
            'company_id' => $r->company_id,
            'company' => $r->company ? [
                'id' => $r->company->id,
                'name' => $r->company->name,
                'public_id' => $r->company->public_id,
            ] : null,
            'scope' => $r->company_id ? 'merchant' : 'platform',
            'name' => $r->name,
            'provider' => $r->provider ? [
                'id' => $r->provider->id,
                'code' => $r->provider->code,
                'name' => $r->provider->name,
                'type' => $r->provider->type,
            ] : null,
            'fallback_provider' => $r->fallbackProvider ? [
                'id' => $r->fallbackProvider->id,
                'code' => $r->fallbackProvider->code,
                'name' => $r->fallbackProvider->name,
            ] : null,
            'mode' => $r->mode,
            'payment_method' => $r->payment_method,
            'currency' => $r->currency,
            'country_code' => $r->country_code,
            'min_amount' => $r->min_amount,
            'max_amount' => $r->max_amount,
            'priority' => $r->priority,
            'traffic_percentage' => $r->traffic_percentage,
            'is_active' => (bool) $r->is_active,
            'created_at' => $r->created_at?->format('M d, Y'),
        ];
    }
}
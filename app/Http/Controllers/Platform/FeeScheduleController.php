<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Payment\FeeSchedule;
use App\Models\Payment\FeeScheduleRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeeScheduleController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       PAGE
       ═══════════════════════════════════════════════════════ */

    public function index()
    {
        return view('fee-schedules.index');
    }

    /* ═══════════════════════════════════════════════════════
       LIST
       ═══════════════════════════════════════════════════════ */

    public function getFeeSchedules(Request $request)
    {
        $query = FeeSchedule::query()
            ->withCount(['rules', 'companies']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->filled('is_default')) {
            $query->where('is_default', $request->boolean('is_default'));
        }

        $schedules = $query->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'current_page' => $schedules->currentPage(),
            'data' => collect($schedules->items())->map(fn ($s) => $this->format($s))->toArray(),
            'from' => $schedules->firstItem(),
            'last_page' => $schedules->lastPage(),
            'next_page_url' => $schedules->nextPageUrl(),
            'prev_page_url' => $schedules->previousPageUrl(),
            'to' => $schedules->lastItem(),
            'total' => $schedules->total(),
        ]);
    }

    public function getStats()
    {
        return response()->json([
            'total'    => FeeSchedule::count(),
            'active'   => FeeSchedule::where('is_active', true)->count(),
            'default'  => FeeSchedule::where('is_default', true)->count(),
            'rules'    => FeeScheduleRule::where('is_active', true)->count(),
            'assigned' => Company::whereNotNull('fee_schedule_id')->count(),
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       SINGLE
       ═══════════════════════════════════════════════════════ */

    public function getFeeSchedule($id)
    {
        try {
            $schedule = FeeSchedule::with(['rules' => fn ($q) => $q->orderBy('priority')->orderBy('id')])
                ->withCount('companies')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge($this->format($schedule), [
                    'rules' => $schedule->rules->map(fn ($r) => $this->formatRule($r))->toArray(),
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fee schedule not found'], 404);
        }
    }

    /* ═══════════════════════════════════════════════════════
       WRITE
       ═══════════════════════════════════════════════════════ */

    public function storeFeeSchedule(Request $request)
    {
        $data = $this->validated($request);

        try {
            return DB::transaction(function () use ($data) {
                $schedule = FeeSchedule::create([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'description' => $data['description'] ?? null,
                    'is_default' => $data['is_default'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                    'effective_from' => $data['effective_from'] ?? null,
                    'effective_to' => $data['effective_to'] ?? null,
                ]);

                // If marked as default, unset any other default
                if ($schedule->is_default) {
                    FeeSchedule::where('id', '!=', $schedule->id)->update(['is_default' => false]);
                }

                foreach ($data['rules'] ?? [] as $ruleData) {
                    $schedule->rules()->create($this->normalizeRule($ruleData));
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Fee schedule created.',
                    'data' => $this->format($schedule->fresh()->loadCount(['rules', 'companies'])),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateFeeSchedule(Request $request, $id)
    {
        try {
            $schedule = FeeSchedule::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fee schedule not found'], 404);
        }

        $data = $this->validated($request, $schedule->id);

        try {
            return DB::transaction(function () use ($schedule, $data) {
                $schedule->update([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'description' => $data['description'] ?? null,
                    'is_default' => $data['is_default'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                    'effective_from' => $data['effective_from'] ?? null,
                    'effective_to' => $data['effective_to'] ?? null,
                ]);

                if ($schedule->is_default) {
                    FeeSchedule::where('id', '!=', $schedule->id)->update(['is_default' => false]);
                }

                // Sync rules: delete removed, update existing, create new
                $incoming = collect($data['rules'] ?? []);
                $incomingIds = $incoming->pluck('id')->filter()->toArray();

                // Delete rules no longer present
                $schedule->rules()->whereNotIn('id', $incomingIds)->delete();

                foreach ($incoming as $ruleData) {
                    $normalized = $this->normalizeRule($ruleData);

                    if (!empty($ruleData['id'])) {
                        $rule = $schedule->rules()->where('id', $ruleData['id'])->first();
                        if ($rule) $rule->update($normalized);
                    } else {
                        $schedule->rules()->create($normalized);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Fee schedule updated.',
                    'data' => $this->format($schedule->fresh()->loadCount(['rules', 'companies'])),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleFeeSchedule($id)
    {
        try {
            $schedule = FeeSchedule::findOrFail($id);
            $schedule->update(['is_active' => !$schedule->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Fee schedule ' . ($schedule->is_active ? 'enabled.' : 'disabled.'),
                'data' => $this->format($schedule->fresh()->loadCount(['rules', 'companies'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle'], 500);
        }
    }

    public function setDefault($id)
    {
        try {
            $schedule = FeeSchedule::findOrFail($id);

            return DB::transaction(function () use ($schedule) {
                FeeSchedule::where('id', '!=', $schedule->id)->update(['is_default' => false]);
                $schedule->update(['is_default' => true, 'is_active' => true]);

                return response()->json([
                    'success' => true,
                    'message' => "{$schedule->name} is now the default schedule.",
                    'data' => $this->format($schedule->fresh()->loadCount(['rules', 'companies'])),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to set default'], 500);
        }
    }

    public function deleteFeeSchedule($id)
    {
        try {
            $schedule = FeeSchedule::withCount('companies')->findOrFail($id);

            // Block delete if any companies are assigned
            if ($schedule->companies_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "{$schedule->companies_count} companies are assigned this schedule. Reassign them first.",
                ], 422);
            }

            if ($schedule->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the default schedule. Set another schedule as default first.',
                ], 422);
            }

            $schedule->delete();

            return response()->json(['success' => true, 'message' => 'Fee schedule deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       VALIDATION + FORMAT
       ═══════════════════════════════════════════════════════ */

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = Rule::unique('fee_schedules', 'code');
        if ($ignoreId) $codeRule->ignore($ignoreId);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:48', 'regex:/^[a-z0-9_]+$/', $codeRule],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],

            'rules' => ['nullable', 'array'],
            'rules.*.id' => ['nullable', 'integer'],
            'rules.*.fee_type' => ['required', 'string', Rule::in([
                'processing', 'refund', 'payout', 'chargeback', 'fx', 'international_card', 'monthly',
            ])],
            'rules.*.payment_method' => ['nullable', 'string', 'max:32'],
            'rules.*.currency' => ['nullable', 'string', 'size:3'],
            'rules.*.country_code' => ['nullable', 'string', 'size:2'],
            'rules.*.card_brand' => ['nullable', 'string', 'max:24'],
            'rules.*.is_international' => ['nullable', 'boolean'],
            'rules.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'rules.*.fixed_amount' => ['nullable', 'integer', 'min:0'],
            'rules.*.fixed_amount_currency' => ['nullable', 'string', 'size:3'],
            'rules.*.minimum_fee' => ['nullable', 'integer', 'min:0'],
            'rules.*.maximum_fee' => ['nullable', 'integer', 'min:0', 'gte:rules.*.minimum_fee'],
            'rules.*.min_transaction_amount' => ['nullable', 'integer', 'min:0'],
            'rules.*.max_transaction_amount' => ['nullable', 'integer', 'min:0'],
            'rules.*.tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rules.*.priority' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rules.*.is_active' => ['boolean'],
        ]);
    }

    protected function normalizeRule(array $rule): array
    {
        return [
            'fee_type' => $rule['fee_type'],
            'payment_method' => $rule['payment_method'] ?: null,
            'currency' => $rule['currency'] ? strtoupper($rule['currency']) : null,
            'country_code' => $rule['country_code'] ? strtoupper($rule['country_code']) : null,
            'card_brand' => $rule['card_brand'] ?: null,
            'is_international' => $rule['is_international'] ?? null,
            'percentage' => $rule['percentage'] ?? 0,
            'fixed_amount' => $rule['fixed_amount'] ?? 0,
            'fixed_amount_currency' => !empty($rule['fixed_amount_currency']) ? strtoupper($rule['fixed_amount_currency']) : null,
            'minimum_fee' => $rule['minimum_fee'] ?? null,
            'maximum_fee' => $rule['maximum_fee'] ?? null,
            'min_transaction_amount' => $rule['min_transaction_amount'] ?? null,
            'max_transaction_amount' => $rule['max_transaction_amount'] ?? null,
            'tax_percentage' => $rule['tax_percentage'] ?? 0,
            'priority' => $rule['priority'] ?? 100,
            'is_active' => $rule['is_active'] ?? true,
        ];
    }

    protected function format(FeeSchedule $s): array
    {
        return [
            'id' => $s->id,
            'uuid' => $s->uuid,
            'name' => $s->name,
            'code' => $s->code,
            'description' => $s->description,
            'is_default' => (bool) $s->is_default,
            'is_active' => (bool) $s->is_active,
            'status_badge' => $s->status_badge,
            'rules_count' => $s->rules_count ?? $s->rules()->count(),
            'companies_count' => $s->companies_count ?? $s->companies()->count(),
            'effective_from' => $s->effective_from?->format('M d, Y'),
            'effective_to' => $s->effective_to?->format('M d, Y'),
            'created_at' => $s->created_at?->format('M d, Y'),
        ];
    }

    protected function formatRule(FeeScheduleRule $r): array
    {
        return [
            'id' => $r->id,
            'fee_type' => $r->fee_type,
            'payment_method' => $r->payment_method,
            'currency' => $r->currency,
            'country_code' => $r->country_code,
            'card_brand' => $r->card_brand,
            'is_international' => $r->is_international,
            'percentage' => (string) $r->percentage,
            'fixed_amount' => $r->fixed_amount,
            'fixed_amount_currency' => $r->fixed_amount_currency,
            'minimum_fee' => $r->minimum_fee,
            'maximum_fee' => $r->maximum_fee,
            'min_transaction_amount' => $r->min_transaction_amount,
            'max_transaction_amount' => $r->max_transaction_amount,
            'tax_percentage' => (string) $r->tax_percentage,
            'priority' => $r->priority,
            'is_active' => (bool) $r->is_active,
            'summary' => $r->summary,
            'scope_label' => $r->scope_label,
        ];
    }
}
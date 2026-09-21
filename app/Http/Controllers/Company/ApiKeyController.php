<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\ApiKey;
use App\Models\Company\ApiRequestLog;
use App\Models\Company\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    /**
     * GET /admin/companies/{companyId}/api-keys
     */
    public function getApiKeys(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $keys = $company->apiKeys()
                ->with('createdBy:id,name,email')
                ->orderByRaw("FIELD(mode, 'live', 'test')")
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($k) => $this->formatKey($k));

            return response()->json(['success' => true, 'data' => $keys]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    /**
     * POST /admin/companies/{companyId}/api-keys
     *
     * The plaintext key is returned exactly once in the response.
     */
    public function storeApiKey(Request $request, $companyId)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', Rule::in(['test', 'live'])],
            'type' => ['required', Rule::in(['publishable', 'secret', 'restricted'])],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:64'],
            'allowed_ips' => ['nullable', 'array'],
            'allowed_ips.*' => ['string', 'max:64'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        try {
            $company = Company::findOrFail($companyId);

            // Guard: live keys require active company + KYB verified
            if ($validated['mode'] === 'live') {
                if ($company->status !== 'active' || $company->kyb_status !== 'verified') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company must be active and KYB-verified to generate live keys.',
                    ], 422);
                }
            }

            $generated = ApiKey::generate($validated['mode'], $validated['type']);

            $key = $company->apiKeys()->create(array_merge(
                $generated['attributes'],
                [
                    'created_by_id' => auth()->id(),
                    'name' => $validated['name'] ?? null,
                    'scopes' => $validated['scopes'] ?? null,
                    'allowed_ips' => $validated['allowed_ips'] ?? null,
                    'rate_limit_per_minute' => $validated['rate_limit_per_minute'] ?? null,
                    'expires_at' => $validated['expires_at'] ?? null,
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'API key generated. Copy it now — it will not be shown again.',
                'data' => array_merge(
                    $this->formatKey($key),
                    ['plaintext' => $generated['plaintext']]   // ← shown once
                ),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /admin/api-keys/{id}
     * Metadata-only — cannot change the key itself.
     */
    public function updateApiKey(Request $request, $id)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'max:64'],
            'allowed_ips' => ['nullable', 'array'],
            'allowed_ips.*' => ['string', 'max:64'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'expires_at' => ['nullable', 'date'],
        ]);

        try {
            $key = ApiKey::findOrFail($id);

            if ($key->revoked_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit a revoked key.',
                ], 422);
            }

            $key->update($request->only([
                'name', 'scopes', 'allowed_ips',
                'rate_limit_per_minute', 'expires_at',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'API key updated.',
                'data' => $this->formatKey($key->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update'], 500);
        }
    }

    /**
     * DELETE /admin/api-keys/{id}
     * Soft-revokes: sets revoked_at + revoked_by_id. Does NOT delete the row
     * because request_logs reference it.
     */
    public function revokeApiKey($id)
    {
        try {
            $key = ApiKey::findOrFail($id);

            if ($key->revoked_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Key is already revoked.',
                ], 422);
            }

            $key->update([
                'revoked_at' => now(),
                'revoked_by_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key revoked. Requests using it will now fail.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to revoke'], 500);
        }
    }

    /**
     * Rotate: revoke current + generate a fresh key of the same mode/type.
     */
    public function rotateApiKey($id)
    {
        try {
            $old = ApiKey::findOrFail($id);

            if ($old->revoked_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot rotate a revoked key.',
                ], 422);
            }

            $generated = ApiKey::generate($old->mode, $old->type);

            $new = $old->company->apiKeys()->create(array_merge(
                $generated['attributes'],
                [
                    'created_by_id' => auth()->id(),
                    'name' => $old->name ? $old->name . ' (rotated)' : null,
                    'scopes' => $old->scopes,
                    'allowed_ips' => $old->allowed_ips,
                    'rate_limit_per_minute' => $old->rate_limit_per_minute,
                    'expires_at' => $old->expires_at,
                ]
            ));

            $old->update([
                'revoked_at' => now(),
                'revoked_by_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Key rotated. The old key is now revoked — copy the new one now.',
                'data' => array_merge(
                    $this->formatKey($new),
                    ['plaintext' => $generated['plaintext']]
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to rotate'], 500);
        }
    }

    /**
     * GET /admin/companies/{companyId}/api-logs
     * Paginated request log for the Logs tab.
     */
    public function getApiLogs(Request $request, $companyId)
    {
        try {
            Company::findOrFail($companyId);

            $query = ApiRequestLog::where('company_id', $companyId)
                ->with('apiKey:id,name,key_prefix,last_four,mode')
                ->orderByDesc('created_at');

            if ($request->filled('mode')) {
                $query->where('mode', $request->mode);
            }
            if ($request->filled('status')) {
                $status = (int) $request->status;
                if ($status >= 400) $query->where('status_code', '>=', 400);
                elseif ($status >= 200) $query->whereBetween('status_code', [200, 299]);
            }
            if ($request->filled('api_key_id')) {
                $query->where('api_key_id', $request->api_key_id);
            }

            $logs = $query->paginate((int) $request->get('per_page', 25));

            return response()->json([
                'current_page' => $logs->currentPage(),
                'data' => $logs->items(),
                'from' => $logs->firstItem(),
                'last_page' => $logs->lastPage(),
                'next_page_url' => $logs->nextPageUrl(),
                'prev_page_url' => $logs->previousPageUrl(),
                'to' => $logs->lastItem(),
                'total' => $logs->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    /**
     * GET /admin/api-logs/{id} — full detail for a single log entry.
     */
    public function getApiLog($id)
    {
        try {
            $log = ApiRequestLog::with([
                'apiKey:id,name,key_prefix,last_four,mode,type',
                'company:id,name,public_id',
            ])->findOrFail($id);

            return response()->json(['success' => true, 'data' => $log]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Log not found'], 404);
        }
    }

    /* ---------- Helpers ---------- */

    protected function formatKey(ApiKey $k): array
    {
        return [
            'id' => $k->id,
            'uuid' => $k->uuid,
            'company_id' => $k->company_id,
            'name' => $k->name,
            'mode' => $k->mode,
            'mode_badge' => $k->mode_badge,
            'type' => $k->type,
            'masked_key' => $k->masked_key,
            'key_prefix' => $k->key_prefix,
            'last_four' => $k->last_four,
            'scopes' => $k->scopes ?? [],
            'allowed_ips' => $k->allowed_ips ?? [],
            'rate_limit_per_minute' => $k->rate_limit_per_minute,
            'status' => $k->status,
            'status_badge' => $k->status_badge,
            'is_active' => $k->is_active,
            'last_used_at' => $k->last_used_at?->format('M d, Y H:i'),
            'last_used_ip' => $k->last_used_ip,
            'expires_at' => $k->expires_at?->format('M d, Y H:i'),
            'revoked_at' => $k->revoked_at?->format('M d, Y H:i'),
            'created_by' => $k->createdBy?->name,
            'created_at' => $k->created_at?->format('M d, Y'),
        ];
    }
}
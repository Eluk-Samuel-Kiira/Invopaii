<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company\Company;
use App\Models\Webhook\Event;
use App\Models\Webhook\EventType;
use App\Models\Webhook\WebhookDelivery;
use App\Models\Webhook\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    /* ═══════════════════════════════════════════════════════
       ENDPOINTS
       ═══════════════════════════════════════════════════════ */

    public function getEndpoints(Request $request, $companyId)
    {
        try {
            $company = Company::findOrFail($companyId);

            $endpoints = $company->webhookEndpoints()
                ->orderByRaw("FIELD(status, 'enabled', 'disabled', 'auto_disabled')")
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($e) => $this->formatEndpoint($e));

            return response()->json(['success' => true, 'data' => $endpoints]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function storeEndpoint(Request $request, $companyId)
    {
        $data = $this->validatedEndpoint($request);

        try {
            $company = Company::findOrFail($companyId);

            // URL must be HTTPS in live mode
            if ($data['mode'] === 'live' && !str_starts_with($data['url'], 'https://')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Live webhook endpoints must use HTTPS.',
                ], 422);
            }

            // Live mode requires active + verified company
            if ($data['mode'] === 'live') {
                if ($company->status !== 'active' || $company->kyb_status !== 'verified') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Company must be active and KYB-verified to create live endpoints.',
                    ], 422);
                }
            }

            $endpoint = $company->webhookEndpoints()->create($data);

            // Return plaintext secret exactly once
            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint created. Save the signing secret now.',
                'data' => array_merge(
                    $this->formatEndpoint($endpoint),
                    ['plaintext_secret' => $endpoint->secret]
                ),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateEndpoint(Request $request, $id)
    {
        try {
            $endpoint = WebhookEndpoint::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Endpoint not found'], 404);
        }

        $data = $this->validatedEndpoint($request, $endpoint->id, $endpoint->company_id);

        try {
            // Live HTTPS check still applies on edit
            if (($data['mode'] ?? $endpoint->mode) === 'live' && !str_starts_with($data['url'] ?? $endpoint->url, 'https://')) {
                return response()->json(['success' => false, 'message' => 'Live endpoints must use HTTPS.'], 422);
            }

            $endpoint->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Endpoint updated.',
                'data' => $this->formatEndpoint($endpoint->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteEndpoint($id)
    {
        try {
            $endpoint = WebhookEndpoint::findOrFail($id);
            $endpoint->delete();

            return response()->json(['success' => true, 'message' => 'Endpoint deleted.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        }
    }

    public function toggleEndpoint($id)
    {
        try {
            $endpoint = WebhookEndpoint::findOrFail($id);

            if ($endpoint->status === 'enabled') {
                $endpoint->update(['status' => 'disabled', 'disabled_at' => now(), 'disabled_reason' => 'Manually disabled']);
            } else {
                $endpoint->update([
                    'status' => 'enabled',
                    'disabled_at' => null,
                    'disabled_reason' => null,
                    'consecutive_failures' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Endpoint ' . ($endpoint->status === 'enabled' ? 'enabled.' : 'disabled.'),
                'data' => $this->formatEndpoint($endpoint->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to toggle'], 500);
        }
    }

    public function rotateSecret($id)
    {
        try {
            $endpoint = WebhookEndpoint::findOrFail($id);

            $newSecret = 'whsec_' . Str::random(48);
            $endpoint->update(['secret' => $newSecret]);

            return response()->json([
                'success' => true,
                'message' => 'Secret rotated. Update your integration now — old signatures will fail.',
                'data' => [
                    'id' => $endpoint->id,
                    'plaintext_secret' => $newSecret,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to rotate secret'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       EVENT TYPES (canonical list for the checkbox grid)
       ═══════════════════════════════════════════════════════ */

    public function getEventTypes()
    {
        $types = EventType::active()
            ->orderBy('resource')
            ->orderBy('name')
            ->get()
            ->groupBy('resource')
            ->map(fn ($group, $resource) => [
                'resource' => $resource,
                'types' => $group->map(fn ($t) => [
                    'name' => $t->name,
                    'description' => $t->description,
                    'category' => $t->category,
                ])->values()->toArray(),
            ])
            ->values();

        return response()->json(['success' => true, 'data' => $types]);
    }

    /* ═══════════════════════════════════════════════════════
       DELIVERIES (logs)
       ═══════════════════════════════════════════════════════ */

    public function getDeliveries(Request $request, $companyId)
    {
        try {
            Company::findOrFail($companyId);

            $query = WebhookDelivery::where('company_id', $companyId)
                ->with([
                    'endpoint:id,url,public_id,mode',
                    'event:id,public_id,type',
                ])
                ->orderByDesc('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('event_type')) {
                $query->where('event_type', $request->event_type);
            }
            if ($request->filled('endpoint_id')) {
                $query->where('webhook_endpoint_id', $request->endpoint_id);
            }

            $deliveries = $query->paginate((int) $request->get('per_page', 25));

            return response()->json([
                'current_page' => $deliveries->currentPage(),
                'data' => collect($deliveries->items())->map(fn ($d) => $this->formatDelivery($d))->toArray(),
                'from' => $deliveries->firstItem(),
                'last_page' => $deliveries->lastPage(),
                'next_page_url' => $deliveries->nextPageUrl(),
                'prev_page_url' => $deliveries->previousPageUrl(),
                'to' => $deliveries->lastItem(),
                'total' => $deliveries->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Company not found'], 404);
        }
    }

    public function getDelivery($id)
    {
        try {
            $delivery = WebhookDelivery::with(['endpoint', 'event'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => array_merge(
                    $this->formatDelivery($delivery),
                    [
                        'payload' => $delivery->event?->toWebhookPayload(),
                        'response_headers' => $delivery->response_headers,
                        'response_body' => $delivery->response_body,
                    ]
                ),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Delivery not found'], 404);
        }
    }

    /**
     * Manual retry — resets the delivery to pending so the job picks it up again.
     */
    public function retryDelivery($id)
    {
        try {
            $delivery = WebhookDelivery::findOrFail($id);

            if ($delivery->status === 'succeeded') {
                return response()->json(['success' => false, 'message' => 'Delivery already succeeded.'], 422);
            }

            $delivery->update([
                'status' => 'pending',
                'next_retry_at' => now(),
                'is_manual_retry' => true,
                'error_class' => null,
                'error_message' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Delivery queued for retry.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to retry'], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════ */

    protected function validatedEndpoint(Request $request, ?int $ignoreId = null, ?int $forceCompanyId = null): array
    {
        $rules = [
            'mode' => ['required', Rule::in(['test', 'live'])],
            'url' => ['required', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled_events' => ['required', 'array', 'min:1'],
            'enabled_events.*' => ['string', 'max:96'],
            'api_version' => ['nullable', 'string', 'max:16'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'custom_headers' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];

        $validated = $request->validate($rules);

        // Validate each event type exists (or is '*')
        foreach ($validated['enabled_events'] as $type) {
            if ($type === '*') continue;
            if (!EventType::where('name', $type)->exists()) {
                abort(response()->json([
                    'success' => false,
                    'message' => "Unknown event type: {$type}",
                ], 422));
            }
        }

        // Defaults
        $validated['timeout_seconds'] = $validated['timeout_seconds'] ?? 10;
        $validated['max_attempts'] = $validated['max_attempts'] ?? 8;

        // Custom headers must be valid header names (no CRLF injection)
        if (!empty($validated['custom_headers'])) {
            foreach ($validated['custom_headers'] as $key => $value) {
                if (!preg_match('/^[A-Za-z0-9-]+$/', $key) || preg_match('/[\r\n]/', (string) $value)) {
                    return tap([], function () {
                        abort(response()->json([
                            'success' => false,
                            'message' => 'Invalid custom header format.',
                        ], 422));
                    });
                }
            }
        }

        return $validated;
    }

    protected function formatEndpoint(WebhookEndpoint $e): array
    {
        return [
            'id' => $e->id,
            'uuid' => $e->uuid,
            'public_id' => $e->public_id,
            'company_id' => $e->company_id,
            'mode' => $e->mode,
            'mode_badge' => $e->mode_badge,
            'url' => $e->url,
            'description' => $e->description,
            'masked_secret' => $e->masked_secret,
            'enabled_events' => $e->enabled_events ?? [],
            'api_version' => $e->api_version,
            'status' => $e->status,
            'status_badge' => $e->status_badge,
            'consecutive_failures' => $e->consecutive_failures,
            'last_success_at' => $e->last_success_at?->format('M d, Y H:i'),
            'last_failure_at' => $e->last_failure_at?->format('M d, Y H:i'),
            'disabled_at' => $e->disabled_at?->format('M d, Y H:i'),
            'disabled_reason' => $e->disabled_reason,
            'timeout_seconds' => $e->timeout_seconds,
            'max_attempts' => $e->max_attempts,
            'created_at' => $e->created_at?->format('M d, Y'),
        ];
    }

    protected function formatDelivery(WebhookDelivery $d): array
    {
        return [
            'id' => $d->id,
            'uuid' => $d->uuid,
            'endpoint' => $d->endpoint ? [
                'id' => $d->endpoint->id,
                'public_id' => $d->endpoint->public_id,
                'url' => $d->endpoint->url,
                'mode' => $d->endpoint->mode,
            ] : null,
            'event' => $d->event ? [
                'id' => $d->event->id,
                'public_id' => $d->event->public_id,
                'type' => $d->event->type,
            ] : null,
            'event_type' => $d->event_type,
            'status' => $d->status,
            'status_badge' => $d->status_badge,
            'attempt' => $d->attempt,
            'response_code' => $d->response_code,
            'duration_ms' => $d->duration_ms,
            'error_class' => $d->error_class,
            'error_message' => $d->error_message,
            'scheduled_for' => $d->scheduled_for?->format('M d, Y H:i:s'),
            'next_retry_at' => $d->next_retry_at?->format('M d, Y H:i:s'),
            'delivered_at' => $d->delivered_at?->format('M d, Y H:i:s'),
            'is_manual_retry' => $d->is_manual_retry,
            'created_at' => $d->created_at?->format('M d, Y H:i:s'),
        ];
    }
}
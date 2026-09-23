<?php

namespace App\Services\Webhook;

use App\Models\Company\Company;
use App\Models\Webhook\Event;
use App\Models\Webhook\WebhookDelivery;
use App\Models\Webhook\WebhookEndpoint;

class EventDispatcher
{
    /**
     * Record an event and fan out deliveries to every subscribed endpoint.
     */
    public static function dispatch(
        Company $company,
        string $type,
        array $data,
        ?object $resource = null,
        ?array $previousAttributes = null,
        string $origin = 'api',
        ?int $triggeredById = null,
        ?string $apiVersion = '2025-01-01',
        ?string $mode = null
    ): Event {
        // Resolve the effective mode: explicit param wins, then the resource's
        // own mode, then the company's current mode, then 'test' as a fallback.
        $effectiveMode = $mode
            ?? $resource?->mode
            ?? $company->current_mode
            ?? 'test';

        $event = Event::create([
            'company_id' => $company->id,
            'mode' => $effectiveMode,
            'type' => $type,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->id,
            'resource_public_id' => $resource?->public_id ?? null,
            'data' => $data,
            'previous_attributes' => $previousAttributes,
            'api_version' => $apiVersion,
            'origin' => $origin,
            'triggered_by_id' => $triggeredById,
            'pending_webhooks' => 0,
        ]);

        $endpoints = WebhookEndpoint::query()
            ->where('company_id', $company->id)
            ->where('mode', $event->mode)
            ->where('status', 'enabled')
            ->get();

        $queued = 0;

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->isSubscribedTo($type)) continue;

            $delivery = WebhookDelivery::create([
                'webhook_endpoint_id' => $endpoint->id,
                'event_id' => $event->id,
                'company_id' => $company->id,
                'mode' => $event->mode,
                'event_type' => $type,
                'status' => 'pending',
                'attempt' => 0,
                'scheduled_for' => now(),
                'next_retry_at' => now(),
            ]);

            \App\Jobs\DeliverWebhook::dispatch($delivery);
            $queued++;
        }

        $event->update(['pending_webhooks' => $queued]);

        return $event;
    }
}
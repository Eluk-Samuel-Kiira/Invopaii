<?php

namespace App\Services\Payment;

use App\Models\Payment\Payment;
use App\Models\Payment\PaymentProvider;
use App\Models\Payment\ProviderWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookReceiver
{
    /**
     * Receive an inbound webhook from a provider. Stores raw, verifies signature,
     * and dispatches an async job to process it.
     *
     * Returns the ProviderWebhookEvent (or null if provider unknown).
     */
    public static function receive(string $providerCode, Request $request): ?ProviderWebhookEvent
    {
        $provider = PaymentProvider::where('code', $providerCode)->first();

        if (!$provider) {
            Log::warning("Webhook received for unknown provider [{$providerCode}]");
            return null;
        }

        $payload = $request->all();
        $rawSignature = $request->header('X-Signature')
            ?? $request->header('Stardena-Signature')
            ?? $request->header('X-Webhook-Signature')
            ?? null;

        // Try to verify signature using the service class if implemented
        $serviceClass = ProviderRouter::resolveProviderClass($providerCode);
        $verified = [
            'valid' => null,
            'event_type' => $payload['type'] ?? null,
            'provider_event_id' => $payload['id'] ?? null,
            'provider_reference' => $payload['reference'] ?? null,
        ];

        if ($serviceClass && class_exists($serviceClass)) {
            $service = new $serviceClass();
            $credentials = $provider->credentials()->where('is_active', true)->get();

            foreach ($credentials as $credential) {
                try {
                    $check = $service->verifyWebhook($request, $credential);
                    if ($check['valid'] ?? false) {
                        $verified = array_merge($verified, $check);
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Webhook verification threw for [{$providerCode}]", ['error' => $e->getMessage()]);
                }
            }
        }

        // Dedupe: if we've seen this event before, return the existing row.
        if (!empty($verified['provider_event_id'])) {
            $existing = ProviderWebhookEvent::where('payment_provider_id', $provider->id)
                ->where('provider_event_id', $verified['provider_event_id'])
                ->first();

            if ($existing) {
                // Track how many times the provider has redelivered this event
                $existing->increment('process_attempts');

                Log::info("Duplicate webhook ignored [{$providerCode}]", [
                    'event_id' => $verified['provider_event_id'],
                    'redelivery_count' => $existing->process_attempts,
                ]);

                return $existing;
            }
        }

        // Store the event
        return ProviderWebhookEvent::create([
            'payment_provider_id' => $provider->id,
            'mode' => self::inferMode($payload),
            'event_type' => $verified['event_type'],
            'provider_event_id' => $verified['provider_event_id'],
            'provider_reference' => $verified['provider_reference'],
            'signature' => $rawSignature,
            'signature_valid' => $verified['valid'],
            'headers' => self::headersToArray($request),
            'payload' => $payload,
            'status' => 'received',
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Process a stored webhook event. Called from the queued job (or synchronously).
     */
    public static function process(ProviderWebhookEvent $event): void
    {
        try {
            $event->increment('process_attempts');

            $payment = null;

            // Find the payment by reference
            if ($event->provider_reference) {
                $payment = Payment::where('provider_reference', $event->provider_reference)
                    ->orWhere('reference', $event->provider_reference)
                    ->first();
            }

            if (!$payment && !empty($event->payload['reference'])) {
                $payment = Payment::where('provider_reference', $event->payload['reference'])
                    ->orWhere('reference', $event->payload['reference'])
                    ->first();
            }

            if (!$payment) {
                $event->update([
                    'status' => 'ignored',
                    'processing_error' => 'No matching payment found',
                    'processed_at' => now(),
                ]);
                return;
            }

            // Handle by event type
            $type = $event->event_type ?? '';

            if (str_contains($type, 'succeeded') || str_contains($type, 'success')) {
                if ($payment->status === 'processing' || $payment->status === 'requires_action') {
                    PaymentStateMachine::markSucceeded($payment, [
                        'provider_status' => 'succeeded',
                    ]);
                }
            } elseif (str_contains($type, 'failed') || str_contains($type, 'failure')) {
                if (!in_array($payment->status, ['succeeded', 'refunded'])) {
                    PaymentStateMachine::markFailed(
                        $payment,
                        $event->payload['message'] ?? 'Provider reported failure',
                        $event->payload['code'] ?? 'provider_webhook_failure'
                    );
                }
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processing_error' => null,
            ]);
        } catch (\Throwable $e) {
            $event->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);
        }
    }

    /* ---------- Helpers ---------- */

    protected static function headersToArray(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            // Redact sensitive headers
            if (in_array(strtolower($key), ['authorization', 'x-api-key', 'cookie'])) {
                $headers[$key] = ['[redacted]'];
                continue;
            }
            $headers[$key] = $values;
        }
        return $headers;
    }

    protected static function inferMode(array $payload, ?PaymentProvider $provider = null): ?string
    {
        if (isset($payload['mode'])) return $payload['mode'];
        if (isset($payload['livemode'])) return $payload['livemode'] ? 'live' : 'test';

        // Try to match the payment by reference to get its mode
        if (!empty($payload['reference'])) {
            $payment = \App\Models\Payment\Payment::where('provider_reference', $payload['reference'])
                ->orWhere('reference', $payload['reference'])
                ->first();
            if ($payment) return $payment->mode;
        }

        return null;
    }

}
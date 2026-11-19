<?php

namespace App\Jobs;

use App\Models\Webhook\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DeliverWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // We manage retries via next_retry_at

    public function __construct(public WebhookDelivery $delivery) {}

    public function handle(): void
    {
        $delivery = $this->delivery->fresh(['endpoint', 'event']);
        if (!$delivery || !$delivery->endpoint || !$delivery->event) return;
        if ($delivery->status === 'succeeded') return;

        $endpoint = $delivery->endpoint;
        $event = $delivery->event;
        $attempt = $delivery->attempt + 1;

        $delivery->update(['status' => 'delivering', 'attempt' => $attempt]);

        // Build the payload
        $body = json_encode($event->toWebhookPayload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Sign it
        $timestamp = time();
        $signature = $endpoint->signPayload($body, $timestamp);

        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Stardena-Webhooks/1.0',
                'Stardena-Event-Id' => $event->public_id,
                'Stardena-Event-Type' => $event->type,
                'Stardena-Signature' => $signature,
                'Stardena-Delivery-Id' => $delivery->uuid,
                'Stardena-Attempt' => (string) $attempt,
            ],
            $endpoint->custom_headers ?? []
        );

        $startedAt = microtime(true);
        $responseCode = null;
        $responseBody = null;
        $responseHeaders = null;
        $errorClass = null;
        $errorMessage = null;
        $success = false;

        try {
            $response = Http::withHeaders($headers)
                ->timeout($endpoint->timeout_seconds ?? 10)
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $responseCode = $response->status();
            $responseBody = substr($response->body(), 0, 4096);
            $responseHeaders = collect($response->headers())->map(fn ($v) => is_array($v) ? $v[0] : $v)->toArray();
            $success = $response->successful();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorClass = 'ConnectionException';
            $errorMessage = 'Could not connect: ' . $e->getMessage();
        } catch (\Exception $e) {
            $errorClass = get_class($e);
            $errorMessage = $e->getMessage();
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($success) {
            $delivery->update([
                'status' => 'succeeded',
                'response_code' => $responseCode,
                'response_body' => $responseBody,
                'response_headers' => $responseHeaders,
                'duration_ms' => $durationMs,
                'delivered_at' => now(),
                'error_class' => null,
                'error_message' => null,
            ]);

            $endpoint->update([
                'last_success_at' => now(),
                'consecutive_failures' => 0,
            ]);

            if ($event->pending_webhooks > 0) {
                $event->decrement('pending_webhooks');
            }

            return;
        }

        // Failed — decide retry or abandon
        $maxAttempts = $endpoint->max_attempts ?? 8;
        $isFinal = $attempt >= $maxAttempts;
        $nextRetry = $isFinal ? null : now()->addSeconds(self::retryDelay($attempt + 1));

        $delivery->update([
            'status' => $isFinal ? 'abandoned' : 'failed',
            'response_code' => $responseCode,
            'response_body' => $responseBody,
            'response_headers' => $responseHeaders,
            'duration_ms' => $durationMs,
            'error_class' => $errorClass,
            'error_message' => $errorMessage,
            'next_retry_at' => $nextRetry,
        ]);

        $endpoint->increment('consecutive_failures');
        $endpoint->update(['last_failure_at' => now()]);

        // Auto-disable after 20 consecutive failures
        if ($endpoint->consecutive_failures >= 20) {
            $endpoint->update([
                'status' => 'auto_disabled',
                'disabled_at' => now(),
                'disabled_reason' => 'Auto-disabled after 20 consecutive failures.',
            ]);
        }

        if ($nextRetry) {
            self::dispatch($delivery)->delay($nextRetry);
        }
    }

    /**
     * Exponential backoff: 30s, 5m, 30m, 2h, 6h, 12h, 24h, 24h...
     */
    protected static function retryDelay(int $attempt): int
    {
        return match (true) {
            $attempt <= 1 => 30,
            $attempt === 2 => 300,
            $attempt === 3 => 1800,
            $attempt === 4 => 7200,
            $attempt === 5 => 21600,
            $attempt === 6 => 43200,
            default => 86400,
        };
    }
}
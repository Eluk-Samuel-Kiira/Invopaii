<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payment\WebhookReceiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProviderWebhookController extends Controller
{
    public function receive(Request $request, string $providerCode)
    {
        // Reject unknown providers immediately
        $event = WebhookReceiver::receive($providerCode, $request);

        if (!$event) {
            return response()->json(['error' => 'Unknown provider'], 404);
        }

        // Queue processing (async) unless the provider needs a synchronous reply
        if ($event->status === 'received') {
            \App\Jobs\ProcessProviderWebhookJob::dispatch($event);
        }

        // Providers expect 200 quickly. Don't wait for processing.
        return response()->json([
            'received' => true,
            'event_id' => $event->uuid,
            'status' => $event->status,
        ], 200);
    }

    /**
     * For providers that use redirect-based callbacks (Pesapal, DPO).
     * The customer returns here, we verify and redirect them onward.
     */
    public function callback(Request $request, string $providerCode)
    {
        $event = WebhookReceiver::receive($providerCode, $request);

        if ($event) {
            \App\Jobs\ProcessProviderWebhookJob::dispatchSync($event);
        }

        // Most redirect-based flows expect you to forward the customer somewhere
        // provider-specific. For now, show a generic "payment received" page.
        return view('webhooks.callback', [
            'provider' => $providerCode,
            'status' => $event?->status ?? 'unknown',
        ]);
    }
}
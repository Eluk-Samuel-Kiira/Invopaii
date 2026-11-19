<?php

namespace App\Jobs;

use App\Models\Payment\ProviderWebhookEvent;
use App\Services\Payment\WebhookReceiver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProviderWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 120, 600, 3600];

    public function __construct(public ProviderWebhookEvent $event) {}

    public function handle(): void
    {
        WebhookReceiver::process($this->event);
    }
}
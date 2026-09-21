<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Webhook\WebhookDelivery;
use App\Jobs\DeliverWebhook;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    WebhookDelivery::whereIn('status', ['pending', 'failed'])
        ->where('next_retry_at', '<=', now())
        ->limit(500)
        ->get()
        ->each(fn ($d) => DeliverWebhook::dispatch($d));
})->everyMinute()->name('webhooks-dispatch-pending');


use App\Jobs\SubscriptionBillingRunJob;
Schedule::job(new SubscriptionBillingRunJob)->hourly()->name('subscription-billing');
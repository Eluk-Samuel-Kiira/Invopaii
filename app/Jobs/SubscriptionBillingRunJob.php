<?php

namespace App\Jobs;

use App\Models\Payment\Subscription;
use App\Services\Payment\SubscriptionBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubscriptionBillingRunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Subscription::dueForBilling()
            ->with(['items.taxRate', 'customer', 'discount', 'company'])
            ->chunk(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    try {
                        SubscriptionBillingService::bill($subscription);
                    } catch (\Exception $e) {
                        \Log::error("Billing failed for subscription {$subscription->id}: {$e->getMessage()}");
                    }
                }
            });
    }
}
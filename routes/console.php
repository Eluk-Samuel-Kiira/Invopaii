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



Schedule::call(function () {
    $accounts = \App\Models\Payment\LedgerAccount::where('category', 'merchant_payable')->get();

    foreach ($accounts as $account) {
        $computed = \App\Models\Payment\LedgerEntry::where('ledger_account_id', $account->id)
            ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) as net")
            ->value('net') ?? 0;

        if ((int) $computed !== (int) $account->balance) {
            \Log::error('Ledger drift detected', [
                'account_id' => $account->id,
                'code' => $account->code,
                'cached' => $account->balance,
                'computed' => $computed,
                'drift' => $account->balance - $computed,
            ]);
        }
    }
})->dailyAt('02:00')->name('ledger-reconciliation');


use App\Jobs\MaturePendingBalancesJob;

Schedule::job(new MaturePendingBalancesJob)->hourly()->name('mature-pending-balances');


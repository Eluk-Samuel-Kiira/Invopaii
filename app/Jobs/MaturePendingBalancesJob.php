<?php

namespace App\Jobs;

use App\Models\Payment\Balance;
use App\Models\Payment\BalanceTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MaturePendingBalancesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $maturable = BalanceTransaction::where('status', 'pending')
            ->whereNotNull('available_on')
            ->where('available_on', '<=', now()->toDateString())
            ->whereNull('payout_id')
            ->get();

        foreach ($maturable as $txn) {
            DB::transaction(function () use ($txn) {
                $balance = Balance::where('id', $txn->balance_id)
                    ->lockForUpdate()
                    ->first();

                if (!$balance) return;

                $amount = $txn->net_amount;

                $balance->decrement('pending_amount', $amount);
                $balance->increment('available_amount', $amount);
                $balance->update(['last_transaction_at' => now()]);

                $txn->update([
                    'status' => 'available',
                    'available_balance_after' => $balance->fresh()->available_amount,
                    'pending_balance_after' => $balance->fresh()->pending_amount,
                ]);
            });
        }
    }
}
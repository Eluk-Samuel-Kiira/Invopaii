<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The merchant-facing wallet, one row per company + currency + mode.
        // Treat these columns as a cache of the ledger, not the source of truth:
        // always update them inside the same transaction as the ledger entries,
        // with lockForUpdate().
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);
            $table->string('currency', 3);

            $table->bigInteger('available_amount')->default(0);  // withdrawable now
            $table->bigInteger('pending_amount')->default(0);    // captured, not yet matured
            $table->bigInteger('reserved_amount')->default(0);   // rolling reserve / dispute holds
            $table->bigInteger('payout_in_transit')->default(0); // sent to bank, not confirmed
            $table->bigInteger('lifetime_volume')->default(0);
            $table->bigInteger('lifetime_fees')->default(0);

            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamp('last_reconciled_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'mode', 'currency'], 'balances_company_mode_currency_unique');
        });

        // The merchant-readable statement line. One row per money movement that
        // touches a balance.
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // txn_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('balance_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('type', 40);
            // charge|refund|refund_failure|fee|fee_refund|dispute|dispute_won|dispute_fee|
            // payout|payout_failure|payout_cancel|adjustment|reserve_hold|reserve_release|
            // transfer|topup|fx_gain|fx_loss

            $table->morphs('source');   // payment, refund, dispute, payout, adjustment
            $table->string('currency', 3);
            $table->bigInteger('gross_amount');    // signed: credits positive, debits negative
            $table->bigInteger('fee_amount')->default(0);
            $table->bigInteger('net_amount');      // what actually moved the balance

            $table->bigInteger('available_balance_after')->nullable(); // running balance snapshot
            $table->bigInteger('pending_balance_after')->nullable();

            $table->string('status', 24)->default('pending'); // pending|available|reserved|paid|reversed
            $table->date('available_on')->nullable();
            $table->string('description')->nullable();
            $table->string('reference')->nullable();

            $table->foreignId('payout_id')->nullable();   // FK added in the payouts migration
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'created_at']);
            $table->index(['balance_id', 'status', 'available_on'], 'bt_balance_status_available_index');
            $table->index(['type', 'created_at']);
            $table->index(['payout_id']);
        });

        // Internal double-entry accounts. Every cent in the system lives in one.
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();   // merchant_payable:42:UGX, platform_revenue:USD
            $table->string('name');
            $table->string('type', 24);             // asset|liability|equity|revenue|expense
            $table->string('normal_balance', 8);    // debit|credit
            $table->string('category', 48)->nullable(); // merchant_payable|platform_revenue|provider_receivable|fx_suspense|reserve
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('currency', 3);
            $table->string('mode', 8);
            $table->bigInteger('balance')->default(0);   // cached, signed by normal_balance
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'currency', 'mode']);
            $table->index(['category', 'currency']);
        });

        // A balanced set of entries. Debits must equal credits — enforce in code
        // and verify with a nightly job.
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('mode', 8);
            $table->string('type', 48);              // payment_captured, fee_charged, payout_paid
            $table->morphs('source');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');    // absolute value of the movement
            $table->string('description')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'posted_at']);
            $table->index(['type', 'posted_at']);
        });

        // Immutable. Never update or delete a posted entry — correct with a reversal.
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->string('direction', 6);          // debit|credit
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);
            $table->bigInteger('balance_after')->nullable();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();

            $table->index(['ledger_account_id', 'posted_at']);
            $table->index('ledger_transaction_id');
        });

        // Manual money movements: goodwill credits, corrections, penalties.
        Schema::create('balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // adj_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('direction', 8);          // credit|debit
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->string('category', 48);          // goodwill|correction|penalty|reserve|writeoff|manual_settlement
            $table->text('reason');
            $table->string('status', 24)->default('pending'); // pending|approved|applied|rejected
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_adjustments');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('ledger_accounts');
        Schema::dropIfExists('balance_transactions');
        Schema::dropIfExists('balances');
    }
};
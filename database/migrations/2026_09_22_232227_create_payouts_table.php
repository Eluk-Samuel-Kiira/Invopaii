<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // po_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('currency', 3);
            $table->unsignedBigInteger('gross_amount');           // sum of included transactions
            $table->unsignedBigInteger('fee_amount')->default(0); // payout fee
            $table->unsignedBigInteger('adjustment_amount')->default(0);
            $table->unsignedBigInteger('amount');                 // what leaves our account

            $table->string('type', 24)->default('scheduled');     // scheduled|manual|instant
            $table->string('method', 32)->default('bank_transfer'); // bank_transfer|mobile_money|wallet
            $table->string('status', 24)->default('pending');
            // pending|approved|processing|in_transit|paid|failed|cancelled|reversed

            // Destination snapshot — bank details can change after the payout
            $table->string('destination_name')->nullable();
            $table->string('destination_bank')->nullable();
            $table->string('destination_last_four', 4)->nullable();

            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference')->nullable();
            $table->string('bank_reference')->nullable();     // what appears on their statement
            $table->string('statement_descriptor', 32)->nullable();

            $table->date('period_start')->nullable();        // transactions covered
            $table->date('period_end')->nullable();
            $table->unsignedInteger('transaction_count')->default(0);

            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->string('failure_code', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->boolean('is_reversed')->default(false);
            $table->text('reversal_reason')->nullable();

            $table->boolean('requires_approval')->default(false);
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('statement_path')->nullable();     // generated payout report

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
            $table->index(['status', 'scheduled_for']);
            $table->index('provider_reference');
            $table->index(['period_start', 'period_end']);
        });

        // Which balance transactions went into which payout.
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('balance_transaction_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->bigInteger('amount');
            $table->string('currency', 3);
            $table->timestamps();

            $table->unique(['payout_id', 'balance_transaction_id'], 'payout_items_unique');
            $table->index('balance_transaction_id');
        });

        // What the provider says they sent us, so finance can reconcile against
        // what we think we're owed.
        Schema::create('provider_settlements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_provider_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('provider_reference')->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->unsignedBigInteger('chargeback_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);
            $table->unsignedInteger('transaction_count')->default(0);

            $table->date('settlement_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();

            $table->string('status', 24)->default('pending'); // pending|received|reconciled|discrepancy
            $table->bigInteger('expected_amount')->nullable();
            $table->bigInteger('variance_amount')->default(0);
            $table->text('discrepancy_notes')->nullable();
            $table->string('report_path')->nullable();       // uploaded CSV from the provider
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['payment_provider_id', 'settlement_date']);
            $table->index('status');
        });

        // Line-level reconciliation between our payments and the provider's report.
        Schema::create('reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider_reference')->nullable();
            $table->string('currency', 3);
            $table->bigInteger('provider_amount')->nullable();
            $table->bigInteger('internal_amount')->nullable();
            $table->bigInteger('variance')->default(0);
            $table->string('status', 24)->default('unmatched'); // matched|unmatched|variance|missing_internal|missing_provider
            $table->text('notes')->nullable();
            $table->json('raw_row')->nullable();
            $table->timestamps();

            $table->index(['provider_settlement_id', 'status'], 'recon_settlement_status_index');
            $table->index('provider_reference');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('payouts')->nullOnDelete();
        });

        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
        });

        Schema::dropIfExists('reconciliation_items');
        Schema::dropIfExists('provider_settlements');
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('payouts');
    }
};
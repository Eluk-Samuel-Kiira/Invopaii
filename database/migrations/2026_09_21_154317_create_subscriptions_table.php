<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // sub_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('description')->nullable();
            $table->string('currency', 3);
            $table->string('status', 24)->default('incomplete');
            // incomplete|trialing|active|past_due|paused|cancelled|unpaid|expired

            $table->string('collection_method', 24)->default('charge_automatically');
            $table->string('billing_interval', 16);          // day|week|month|year
            $table->unsignedSmallInteger('billing_interval_count')->default(1);
            $table->unsignedTinyInteger('billing_cycle_anchor_day')->nullable();

            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('cancel_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('cancellation_reason')->nullable();

            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();
            $table->unsignedSmallInteger('failed_payment_attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->unsignedInteger('invoices_generated')->default(0);
            $table->unsignedBigInteger('lifetime_amount')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'status']);
            $table->index(['status', 'next_billing_at']);
            $table->index('customer_id');
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->nullable()->constrained('prices')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('name');
            $table->unsignedBigInteger('unit_amount');
            $table->string('currency', 3);
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('subscription_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
        });

        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
    }
};
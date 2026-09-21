<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gapless per-company invoice numbering. Increment inside a transaction
        // with lockForUpdate(); never derive the number from count().
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);
            $table->string('prefix', 16)->default('INV');
            $table->unsignedInteger('year')->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->timestamps();

            $table->unique(['company_id', 'mode', 'prefix', 'year'], 'invoice_seq_unique');
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // inv_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('number', 64)->nullable();    // INV-2026-0042, assigned on finalisation
            $table->string('access_token', 80)->unique(); // hosted invoice URL secret

            // Snapshot of the customer at issue time — invoices must not change retroactively
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->json('customer_address')->nullable();
            $table->json('company_snapshot')->nullable(); // issuer details, logo, tax id

            $table->string('currency', 3);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->unsignedBigInteger('amount_due')->default(0);
            $table->unsignedBigInteger('amount_refunded')->default(0);

            $table->foreignId('discount_id')->nullable()->constrained('discounts')->nullOnDelete();

            $table->string('status', 24)->default('draft'); // draft|open|paid|partially_paid|past_due|void|uncollectible
            $table->string('collection_method', 24)->default('send_invoice'); // send_invoice|charge_automatically
            $table->boolean('allow_partial_payment')->default(false);

            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);

            $table->string('hosted_url', 2048)->nullable();
            $table->string('pdf_path')->nullable();
            $table->foreignId('payment_link_id')->nullable()->constrained()->nullOnDelete();

            $table->text('notes')->nullable();          // visible to customer
            $table->text('terms')->nullable();
            $table->text('internal_notes')->nullable(); // never rendered

            // Recurrence
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('parent_invoice_id')->nullable()->references('id')->on('invoices')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable(); // FK added in the subscriptions migration
            $table->string('recurrence_interval', 16)->nullable();
            $table->unsignedSmallInteger('recurrence_interval_count')->nullable();
            $table->date('next_issue_date')->nullable();

            $table->boolean('auto_reminders_enabled')->default(true);
            $table->unsignedSmallInteger('reminders_sent')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'mode', 'number'], 'invoices_company_mode_number_unique');
            $table->index(['company_id', 'mode', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_id')->nullable()->constrained('prices')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->string('unit_label', 32)->nullable();
            $table->unsignedBigInteger('unit_amount');
            $table->string('currency', 3);

            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('tax_percentage', 6, 3)->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });

        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);          // before_due|on_due|after_due|manual
            $table->integer('offset_days')->default(0);  // negative = before due date
            $table->string('channel', 16)->default('email'); // email|sms|whatsapp
            $table->string('status', 24)->default('scheduled'); // scheduled|sent|failed|cancelled
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index('invoice_id');
        });

        // Link a checkout session back to its invoice now that invoices exist.
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::dropIfExists('invoice_reminders');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
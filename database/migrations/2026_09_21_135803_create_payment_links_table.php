<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A reusable, shareable URL that collects money. The headline feature.
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // plink_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('slug', 64);                  // pay.stardena.com/<slug>
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();

            // Amount behaviour
            $table->string('amount_type', 16)->default('fixed'); // fixed|customer_chooses|line_items
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount')->nullable();       // null when customer chooses
            $table->unsignedBigInteger('minimum_amount')->nullable();
            $table->unsignedBigInteger('maximum_amount')->nullable();
            $table->json('suggested_amounts')->nullable();          // quick-pick buttons
            $table->boolean('allow_quantity_adjustment')->default(false);

            // Collection behaviour
            $table->boolean('collect_customer_name')->default(true);
            $table->boolean('collect_email')->default(true);
            $table->boolean('collect_phone')->default(false);
            $table->boolean('collect_billing_address')->default(false);
            $table->boolean('collect_shipping_address')->default(false);
            $table->json('custom_fields')->nullable();  // [{key,label,type,required,options}]
            $table->json('allowed_payment_methods')->nullable(); // null = all enabled for the country
            $table->json('allowed_countries')->nullable();

            // Limits
            $table->string('usage_type', 16)->default('multi_use'); // single_use|multi_use
            $table->unsignedInteger('max_payments')->nullable();
            $table->unsignedInteger('payments_count')->default(0);
            $table->unsignedBigInteger('amount_collected')->default(0);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('expires_at')->nullable();

            // After payment
            $table->string('after_completion', 24)->default('hosted_confirmation'); // hosted_confirmation|redirect
            $table->string('success_url', 2048)->nullable();
            $table->string('cancel_url', 2048)->nullable();
            $table->text('success_message')->nullable();
            $table->boolean('send_receipt')->default(true);

            // Fees & reference
            $table->boolean('pass_fees_to_customer')->default(false);
            $table->string('reference_prefix', 24)->nullable();
            $table->string('statement_descriptor', 22)->nullable();

            $table->string('status', 24)->default('active'); // active|inactive|expired|completed|archived
            $table->boolean('requires_password')->default(false);
            $table->string('password_hash')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['mode', 'slug']);
            $table->index(['company_id', 'mode', 'status']);
        });

        Schema::create('payment_link_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_id')->nullable()->constrained('prices')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('unit_amount');
            $table->string('currency', 3);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('min_quantity')->nullable();
            $table->unsignedInteger('max_quantity')->nullable();
            $table->boolean('is_adjustable')->default(false);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('payment_link_id');
        });

        // One customer's attempt to pay: from a link, an invoice, or the API.
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // cs_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->foreignId('payment_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable();     // FK added in the invoices migration
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable();     // FK added in the payments migration

            $table->string('session_token', 80)->unique();   // the URL secret
            $table->string('url', 2048)->nullable();

            $table->string('currency', 3);
            $table->unsignedBigInteger('amount_subtotal')->default(0);
            $table->unsignedBigInteger('amount_discount')->default(0);
            $table->unsignedBigInteger('amount_tax')->default(0);
            $table->unsignedBigInteger('amount_fee')->default(0);   // when fees are passed on
            $table->unsignedBigInteger('amount_total')->default(0);

            // Captured from the customer during checkout
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('customer_country', 2)->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('custom_field_values')->nullable();

            $table->string('selected_payment_method', 32)->nullable();
            $table->string('status', 24)->default('open');       // open|processing|complete|expired|cancelled
            $table->string('payment_status', 24)->default('unpaid'); // unpaid|paid|no_payment_required

            $table->string('locale', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('referrer')->nullable();

            $table->string('success_url', 2048)->nullable();
            $table->string('cancel_url', 2048)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
            $table->index(['payment_link_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('checkout_session_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('unit_amount');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('amount_subtotal');
            $table->unsignedBigInteger('amount_tax')->default(0);
            $table->unsignedBigInteger('amount_total');
            $table->string('currency', 3);
            $table->timestamps();

            $table->index('checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_session_items');
        Schema::dropIfExists('checkout_sessions');
        Schema::dropIfExists('payment_link_items');
        Schema::dropIfExists('payment_links');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // prod_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('sku')->nullable();
            $table->string('unit_label', 32)->nullable();   // "seat", "hour"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_shippable')->default(false);
            $table->string('tax_code', 32)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'is_active']);
            $table->index('sku');
        });

        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // price_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('nickname')->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('unit_amount');        // minor units
            $table->string('billing_scheme', 24)->default('per_unit'); // per_unit|tiered
            $table->json('tiers')->nullable();

            $table->string('type', 16)->default('one_time');  // one_time|recurring
            $table->string('recurring_interval', 16)->nullable(); // day|week|month|year
            $table->unsignedSmallInteger('recurring_interval_count')->nullable();
            $table->unsignedSmallInteger('trial_period_days')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('tax_inclusive')->default(false);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'is_active']);
            $table->index(['product_id', 'currency']);
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // txr_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('display_name');               // VAT, Sales tax
            $table->decimal('percentage', 6, 3);          // 18.000
            $table->boolean('is_inclusive')->default(false);
            $table->string('country_code', 2)->nullable();
            $table->string('state')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->string('tax_type', 32)->nullable();   // vat|gst|sales_tax|withholding
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'mode', 'is_active']);
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // disc_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('code', 64)->nullable();       // customer-facing coupon code
            $table->string('name')->nullable();
            $table->string('type', 16);                   // percentage|fixed_amount
            $table->decimal('percent_off', 6, 3)->nullable();
            $table->unsignedBigInteger('amount_off')->nullable();
            $table->string('currency', 3)->nullable();

            $table->string('duration', 16)->default('once'); // once|repeating|forever
            $table->unsignedSmallInteger('duration_in_months')->nullable();

            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('times_redeemed')->default(0);
            $table->unsignedInteger('max_redemptions_per_customer')->nullable();
            $table->unsignedBigInteger('minimum_order_amount')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('applies_to_product_ids')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'mode', 'code'], 'discounts_company_mode_code_unique');
            $table->index(['company_id', 'mode', 'is_active']);
        });

        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('redeemable_type')->nullable();   // invoice, checkout_session
            $table->unsignedBigInteger('redeemable_id')->nullable();
            $table->unsignedBigInteger('amount_discounted');
            $table->string('currency', 3);
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamps();

            $table->index(['discount_id', 'customer_id']);
            $table->index(['redeemable_type', 'redeemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('prices');
        Schema::dropIfExists('products');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // cus_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('reference')->nullable();     // merchant's own customer id
            $table->text('description')->nullable();

            $table->string('country_code', 2)->nullable();
            $table->string('preferred_currency', 3)->nullable();
            $table->string('preferred_locale', 10)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->foreignId('default_payment_method_id')->nullable(); // FK added below
            $table->foreignId('default_address_id')->nullable();

            // Running totals, denormalised for the customer list view.
            $table->unsignedBigInteger('lifetime_value')->default(0); // minor units, settlement currency
            $table->unsignedInteger('successful_payments_count')->default(0);
            $table->unsignedInteger('disputed_payments_count')->default(0);
            $table->timestamp('first_paid_at')->nullable();
            $table->timestamp('last_paid_at')->nullable();

            $table->boolean('is_blocked')->default(false);
            $table->string('blocked_reason')->nullable();
            $table->boolean('tax_exempt')->default(false);
            $table->string('tax_id')->nullable();

            $table->json('shipping_address')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'mode', 'email'], 'customers_company_mode_email_unique');
            $table->index(['company_id', 'mode', 'created_at']);
            $table->index('phone');
            $table->index('reference');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16)->default('billing'); // billing|shipping
            $table->string('name')->nullable();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country_code', 2);
            $table->string('phone', 32)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'type']);
        });

        // Tokenised instruments. No raw PAN is ever stored — only the network token
        // or the provider's token reference, plus display metadata.
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // pm_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('type', 32);      // card|bank_account|mobile_money|wallet|bank_transfer|ussd

            // Card
            $table->string('card_brand', 24)->nullable();      // visa|mastercard|amex
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_bin', 8)->nullable();         // for routing and risk
            $table->unsignedTinyInteger('card_exp_month')->nullable();
            $table->unsignedSmallInteger('card_exp_year')->nullable();
            $table->string('card_funding', 16)->nullable();    // credit|debit|prepaid
            $table->string('card_country', 2)->nullable();
            $table->string('card_issuer')->nullable();
            $table->string('three_d_secure_support', 16)->nullable();

            // Bank / mobile money
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 32)->nullable();
            $table->string('account_last_four', 4)->nullable();
            $table->string('mobile_network', 32)->nullable();
            $table->string('msisdn_last_four', 4)->nullable();

            // Tokens
            $table->string('provider', 64)->nullable();        // which processor holds the token
            $table->text('provider_token')->nullable();        // encrypted
            $table->string('fingerprint', 128)->nullable();    // same instrument across customers
            $table->boolean('is_reusable')->default(true);
            $table->boolean('is_default')->default(false);

            $table->string('status', 24)->default('active');   // active|expired|revoked|failed
            $table->timestamp('last_used_at')->nullable();
            $table->json('billing_details')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'type']);
            $table->index(['customer_id', 'is_default']);
            $table->index('fingerprint');
            $table->index('card_bin');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('default_payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('default_address_id')->references('id')->on('customer_addresses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['default_payment_method_id']);
            $table->dropForeign(['default_address_id']);
        });

        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
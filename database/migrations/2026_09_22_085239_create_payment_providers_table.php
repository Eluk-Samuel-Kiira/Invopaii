<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The upstream processors and acquirers Stardena sits on top of.
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 48)->unique();     // flutterwave, stripe, mtn_momo, dpo
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('type', 32);               // acquirer|aggregator|mobile_money|bank|wallet

            $table->json('supported_countries')->nullable();  // ["UG","KE"]
            $table->json('supported_currencies')->nullable();
            $table->json('supported_methods')->nullable();    // ["card","mobile_money"]
            $table->json('capabilities')->nullable();         // {"refunds":true,"partial_refunds":false,"payouts":true}

            $table->boolean('supports_test_mode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(100); // lower wins in routing
            $table->decimal('success_rate', 5, 2)->nullable();      // rolling, updated by a job
            $table->unsignedInteger('avg_latency_ms')->nullable();
            $table->string('health_status', 16)->default('healthy'); // healthy|degraded|down
            $table->timestamp('health_checked_at')->nullable();

            $table->string('webhook_path')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });

        // Platform-level or per-merchant credentials. All secrets encrypted.
        Schema::create('provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = platform-wide
            $table->string('mode', 8);

            $table->string('label')->nullable();
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();       // encrypted
            $table->text('webhook_secret')->nullable();   // encrypted
            $table->string('merchant_account_id')->nullable();
            $table->json('extra')->nullable();            // encrypted cast; provider-specific fields

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_provider_id', 'company_id', 'mode'], 'provider_credentials_unique');
        });

        // Decides which provider handles a given payment.
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = global
            $table->foreignId('payment_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('payment_providers')->nullOnDelete();
            $table->string('mode', 8)->nullable();

            $table->string('name')->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('card_brand', 24)->nullable();
            $table->unsignedBigInteger('min_amount')->nullable();
            $table->unsignedBigInteger('max_amount')->nullable();
            $table->json('conditions')->nullable();       // anything more exotic

            $table->unsignedSmallInteger('priority')->default(100);
            $table->unsignedTinyInteger('traffic_percentage')->default(100); // for A/B splitting
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'mode', 'is_active']);
            $table->index(['payment_method', 'currency', 'country_code'], 'routing_method_currency_country_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
        Schema::dropIfExists('provider_credentials');
        Schema::dropIfExists('payment_providers');
    }
};
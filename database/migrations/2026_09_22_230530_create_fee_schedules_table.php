<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A named pricing package. Companies point at one; leave company_id null
        // for the platform defaults.
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 48)->unique();       // standard_africa, enterprise_2026
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();
        });

        // The actual rate card. Most specific match wins.
        Schema::create('fee_schedule_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_schedule_id')->constrained()->cascadeOnDelete();

            $table->string('fee_type', 32);              // processing|refund|chargeback|payout|fx|international_card|monthly
            $table->string('payment_method', 32)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('card_brand', 24)->nullable();
            $table->boolean('is_international')->nullable();  // issuer country != merchant country

            $table->decimal('percentage', 8, 4)->default(0);  // 2.9000
            $table->unsignedBigInteger('fixed_amount')->default(0); // minor units
            $table->string('fixed_amount_currency', 3)->nullable();
            $table->unsignedBigInteger('minimum_fee')->nullable();
            $table->unsignedBigInteger('maximum_fee')->nullable();     // fee cap
            $table->unsignedBigInteger('min_transaction_amount')->nullable();
            $table->unsignedBigInteger('max_transaction_amount')->nullable();

            $table->decimal('tax_percentage', 6, 3)->default(0);  // VAT on the fee itself
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['fee_schedule_id', 'fee_type', 'is_active'], 'fee_rules_schedule_type_active_index');
            $table->index(['payment_method', 'currency', 'country_code'], 'fee_rules_method_currency_country_index');
        });

        // Every fee actually charged, itemised. This is what the merchant sees
        // in the fee breakdown and what finance reconciles against.
        Schema::create('applied_fees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->morphs('feeable');   // payment, refund, dispute, payout
            $table->foreignId('fee_schedule_rule_id')->nullable()->constrained()->nullOnDelete();

            $table->string('fee_type', 32);
            $table->string('description')->nullable();
            $table->string('currency', 3);
            $table->unsignedBigInteger('base_amount');       // amount the fee was computed on
            $table->decimal('percentage_applied', 8, 4)->default(0);
            $table->unsignedBigInteger('percentage_component')->default(0);
            $table->unsignedBigInteger('fixed_component')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total_amount');

            $table->boolean('is_passed_to_customer')->default(false);
            $table->boolean('is_waived')->default(false);
            $table->string('waiver_reason')->nullable();
            $table->json('calculation_snapshot')->nullable(); // how we got the number, for disputes

            $table->timestamps();

            $table->index(['company_id', 'mode', 'fee_type', 'created_at'], 'applied_fees_company_mode_type_index');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('fee_schedule_id')->references('id')->on('fee_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['fee_schedule_id']);
        });

        Schema::dropIfExists('applied_fees');
        Schema::dropIfExists('fee_schedule_rules');
        Schema::dropIfExists('fee_schedules');
    }
};
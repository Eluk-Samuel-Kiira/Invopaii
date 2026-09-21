<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where the merchant's money goes. Account numbers are encrypted at the model
        // layer; last_four is kept in clear text for display and matching.
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();  // ba_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('type', 32)->default('bank_account'); // bank_account|mobile_money|wallet
            $table->string('currency', 3);
            $table->string('country_code', 2);

            $table->string('account_holder_name');
            $table->string('account_holder_type', 24)->default('company'); // individual|company

            // Bank rails
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 32)->nullable();
            $table->string('branch_code', 32)->nullable();
            $table->text('account_number')->nullable();   // encrypted
            $table->text('iban')->nullable();             // encrypted
            $table->string('swift_bic', 16)->nullable();
            $table->string('routing_number', 32)->nullable();
            $table->string('sort_code', 16)->nullable();

            // Mobile money rails
            $table->string('mobile_network', 32)->nullable(); // mtn|airtel|mpesa
            $table->text('msisdn')->nullable();               // encrypted

            $table->string('last_four', 4)->nullable();
            $table->string('fingerprint', 128)->nullable();   // hash, to detect duplicates

            $table->boolean('is_default')->default(false);
            $table->string('status', 24)->default('new');     // new|validated|verified|errored|disabled
            $table->string('verification_method', 32)->nullable(); // micro_deposit|name_lookup|document
            $table->timestamp('verified_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'currency', 'is_default']);
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
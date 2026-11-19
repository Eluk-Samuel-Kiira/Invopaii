<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = platform rule
            $table->string('mode', 8)->nullable();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('scope', 24)->default('payment');  // payment|payout|company|customer
            $table->json('conditions');   // [{field:"amount",operator:"gt",value:500000}]
            $table->string('action', 24); // allow|review|block|require_3ds|challenge
            $table->unsignedSmallInteger('score_weight')->default(0);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('times_triggered')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'is_active']);
            $table->index(['scope', 'is_active']);
        });

        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            // The object being assessed — nullable because a pre-payment
            // assessment can run before the payment exists.
            $table->string('assessable_type')->nullable();
            $table->unsignedBigInteger('assessable_id')->nullable();

            $table->unsignedSmallInteger('score')->default(0);   // 0-100
            $table->string('level', 16)->default('normal');       // normal|elevated|highest|blocked
            $table->string('outcome', 24);                        // allowed|reviewed|blocked|challenged
            $table->json('triggered_rules')->nullable();          // rule ids + weights
            $table->json('signals')->nullable();                  // ip velocity, bin country mismatch, etc.

            $table->string('provider', 64)->nullable();           // external fraud engine
            $table->string('provider_reference')->nullable();
            $table->unsignedSmallInteger('provider_score')->nullable();

            $table->boolean('manually_reviewed')->default(false);
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_decision', 24)->nullable();    // approve|reject
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['assessable_type', 'assessable_id'], 'risk_assessable_morph_index');
            $table->index(['company_id', 'mode', 'level']);
            $table->index(['outcome', 'created_at']);
        });

        // Merchant-managed and platform-managed deny lists.
        Schema::create('blocklist_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete(); // null = platform-wide
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8)->nullable();

            $table->string('type', 32);      // email|ip|card_fingerprint|card_bin|phone|device|country|domain
            $table->string('value', 255);
            $table->string('value_hash', 128)->nullable(); // for PII, index the hash
            $table->string('list', 16)->default('block');  // block|allow|review
            $table->string('reason')->nullable();
            $table->string('source', 32)->default('manual'); // manual|auto|dispute|provider
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'type', 'value'], 'blocklist_company_type_value_unique');
            $table->index(['type', 'value_hash', 'is_active']);
        });

        // Configurable ceilings per company, enforced before a charge is attempted.
        Schema::create('velocity_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('mode', 8)->nullable();
            $table->string('scope', 24);      // company|customer|card|ip|email
            $table->string('window', 16);     // minute|hour|day|week|month
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('max_count')->nullable();
            $table->unsignedBigInteger('max_amount')->nullable();
            $table->string('action', 24)->default('block'); // block|review
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'scope', 'window'], 'velocity_company_scope_window_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('velocity_limits');
        Schema::dropIfExists('blocklist_entries');
        Schema::dropIfExists('risk_assessments');
        Schema::dropIfExists('risk_rules');
    }
};
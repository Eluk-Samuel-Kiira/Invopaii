<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // acct_xxx — exposed in the API

            // Identity
            $table->string('name');                       // trading / display name
            $table->string('legal_name')->nullable();     // as registered
            $table->string('slug')->unique();             // stardena.dev/pay/<slug>
            $table->string('email')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 32)->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 9)->nullable();

            // Registration
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->string('business_type', 32)->default('company'); // individual|company|ngo|government
            $table->string('industry', 64)->nullable();
            $table->string('mcc', 8)->nullable();                     // merchant category code
            $table->string('registration_number')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->date('incorporated_on')->nullable();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();

            // Money config
            $table->string('default_currency', 3)->default('USD');
            $table->string('settlement_currency', 3)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('statement_descriptor', 22)->nullable(); // what shows on the cardholder statement

            // Lifecycle
            $table->string('status', 32)->default('pending');   // pending|in_review|active|restricted|suspended|rejected|closed
            $table->string('kyb_status', 32)->default('unverified'); // unverified|pending|verified|rejected
            $table->boolean('live_mode_enabled')->default(false);
            $table->boolean('charges_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->string('onboarding_step', 64)->nullable();
            $table->json('requirements_due')->nullable();        // what compliance still needs
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            // Commercials & risk
            $table->foreignId('fee_schedule_id')->nullable();    // FK added in the fees migration
            $table->string('risk_level', 16)->default('standard'); // low|standard|elevated|high
            $table->unsignedSmallInteger('risk_score')->default(0);
            $table->string('payout_schedule', 16)->default('daily'); // manual|daily|weekly|monthly
            $table->unsignedSmallInteger('payout_delay_days')->default(2); // T+n settlement
            $table->decimal('reserve_percent', 5, 2)->default(0);  // rolling reserve
            $table->unsignedSmallInteger('reserve_hold_days')->default(0);

            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'kyb_status']);
            $table->index('country_id');
            $table->index('risk_level');
        });

        // Team membership. A user can belong to several companies.
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('member'); // owner|admin|developer|finance|support|view_only
            $table->json('permissions')->nullable();       // per-membership overrides
            $table->boolean('can_access_live_mode')->default(false);
            $table->string('status', 24)->default('active'); // active|suspended
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32)->default('member');
            $table->string('token', 100)->unique();
            $table->string('status', 24)->default('pending'); // pending|accepted|revoked|expired
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
        Schema::dropIfExists('company_user');
        Schema::dropIfExists('companies');
    }
};
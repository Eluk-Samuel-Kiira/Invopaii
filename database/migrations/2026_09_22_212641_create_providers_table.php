<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The central object. One payment = one intent to move money from a
        // customer to a merchant, regardless of how many provider attempts it took.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // pay_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            // Where it came from
            $table->string('source', 32)->default('api'); // api|payment_link|invoice|checkout|dashboard|subscription|recurring
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('payment_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            // Presentment amounts — what the customer was charged
            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');                 // requested
            $table->unsignedBigInteger('amount_captured')->default(0);
            $table->unsignedBigInteger('amount_refunded')->default(0);
            $table->unsignedBigInteger('amount_disputed')->default(0);
            $table->unsignedBigInteger('amount_tip')->default(0);
            $table->unsignedBigInteger('amount_fee_passed_on')->default(0); // surcharge paid by customer

            // Settlement amounts — what hits the merchant balance
            $table->string('settlement_currency', 3)->nullable();
            $table->unsignedBigInteger('settlement_amount')->nullable();
            $table->decimal('exchange_rate', 24, 12)->nullable();
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();

            // Fees
            $table->unsignedBigInteger('fee_amount')->default(0);
            $table->unsignedBigInteger('tax_on_fee_amount')->default(0);
            $table->bigInteger('net_amount')->default(0);   // settlement_amount - fees

            // Status machine
            $table->string('status', 32)->default('requires_payment_method');
            // requires_payment_method|requires_confirmation|requires_action|processing|
            // requires_capture|succeeded|partially_refunded|refunded|failed|cancelled|expired|reversed
            $table->string('capture_method', 16)->default('automatic'); // automatic|manual
            $table->string('confirmation_method', 16)->default('automatic');

            // Instrument snapshot — denormalised so the payment row stands alone
            $table->string('payment_method_type', 32)->nullable(); // card|mobile_money|bank_transfer|ussd|wallet
            $table->string('card_brand', 24)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_bin', 8)->nullable();
            $table->string('card_country', 2)->nullable();
            $table->string('mobile_network', 32)->nullable();
            $table->string('bank_name')->nullable();

            // Provider
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference')->nullable();     // their id
            $table->string('provider_authorization_code')->nullable();
            $table->string('acquirer_reference')->nullable();     // ARN / RRN, needed for disputes
            $table->string('network_transaction_id')->nullable();

            // Authentication
            $table->boolean('three_d_secure_used')->default(false);
            $table->string('three_d_secure_result', 32)->nullable();
            $table->string('avs_result', 8)->nullable();
            $table->string('cvv_result', 8)->nullable();

            // Customer-facing
            $table->string('reference')->nullable();       // merchant's own reference
            $table->text('description')->nullable();
            $table->string('statement_descriptor', 22)->nullable();
            $table->string('receipt_email')->nullable();
            $table->string('receipt_number', 64)->nullable();
            $table->string('receipt_url', 2048)->nullable();
            $table->timestamp('receipt_sent_at')->nullable();

            // Next action, for redirects, OTP and USSD flows
            $table->string('next_action_type', 32)->nullable(); // redirect|otp|ussd|qr|wait_for_push
            $table->json('next_action')->nullable();
            $table->string('return_url', 2048)->nullable();

            // Risk
            $table->unsignedSmallInteger('risk_score')->nullable();
            $table->string('risk_level', 16)->nullable();   // normal|elevated|highest|blocked
            $table->boolean('requires_manual_review')->default(false);

            // Failure
            $table->string('failure_code', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->string('decline_reason', 64)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);

            // Dispute & settlement state
            $table->boolean('is_disputed')->default(false);
            $table->boolean('is_settled')->default(false);
            $table->date('available_on')->nullable();      // when funds become withdrawable
            $table->foreignId('payout_id')->nullable();    // FK added in the payouts migration

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('customer_country', 2)->nullable();
            $table->string('idempotency_key')->nullable();

            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'status', 'created_at'], 'payments_company_mode_status_created_index');
            $table->index(['company_id', 'mode', 'created_at']);
            $table->index(['customer_id', 'status']);
            $table->index(['provider_reference']);
            $table->index(['payment_provider_id', 'status']);
            $table->index('reference');
            $table->index('available_on');
            $table->index(['is_settled', 'payout_id']);
            $table->index('card_bin');
        });

        // One row per call to a provider. Retries and fallbacks each get a row,
        // so you can see exactly why a payment eventually succeeded or failed.
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 8);

            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('operation', 32)->default('charge'); // charge|authorize|capture|verify|cancel
            $table->string('status', 32);                       // initiated|pending|succeeded|failed|timeout
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);

            $table->string('provider_reference')->nullable();
            $table->string('provider_status', 64)->nullable();
            $table->string('provider_code', 64)->nullable();
            $table->text('provider_message')->nullable();
            $table->json('request_payload')->nullable();   // redact card data
            $table->json('response_payload')->nullable();

            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_fallback')->default(false);
            $table->boolean('is_retry')->default(false);
            $table->text('failure_reason')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'attempt_number']);
            $table->index(['payment_provider_id', 'status', 'created_at']);
            $table->index('provider_reference');
        });

        // Raw inbound webhooks from providers. Store first, process async,
        // dedupe on signature — providers resend aggressively.
        Schema::create('provider_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 8)->nullable();
            $table->string('event_type', 96)->nullable();
            $table->string('provider_event_id')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('signature', 512)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->string('status', 24)->default('received'); // received|processed|ignored|failed|duplicate
            $table->unsignedSmallInteger('process_attempts')->default(0);
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->unique(['payment_provider_id', 'provider_event_id'], 'provider_event_unique');
            $table->index(['status', 'created_at']);
            $table->index('provider_reference');
        });

        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
        });

        Schema::dropIfExists('provider_webhook_events');
        Schema::dropIfExists('payment_attempts');
        Schema::dropIfExists('payments');
    }
};
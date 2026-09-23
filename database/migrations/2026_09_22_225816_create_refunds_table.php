<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // ref_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('settlement_amount')->nullable();
            $table->bigInteger('fee_refunded')->default(0);   // portion of our fee given back, if any
            $table->boolean('is_partial')->default(false);
            $table->boolean('refund_application_fee')->default(false);

            $table->string('reason', 48)->nullable();      // requested_by_customer|duplicate|fraudulent|expired_uncaptured
            $table->text('description')->nullable();
            $table->string('status', 24)->default('pending'); // pending|processing|succeeded|failed|cancelled
            $table->string('source', 24)->default('api');     // api|dashboard|dispute|system

            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->text('failure_message')->nullable();

            $table->string('idempotency_key')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('expected_arrival_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
            $table->index(['payment_id', 'status']);
            $table->index('provider_reference');
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // dp_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode', 8);

            $table->string('currency', 3);
            $table->unsignedBigInteger('amount');            // disputed amount
            $table->unsignedBigInteger('fee_amount')->default(0); // chargeback fee we charge the merchant

            $table->string('type', 32)->default('chargeback'); // inquiry|retrieval|chargeback|pre_arbitration|arbitration
            $table->string('reason', 64)->nullable();          // fraudulent|product_not_received|duplicate|subscription_cancelled
            $table->string('reason_code', 32)->nullable();     // network code, e.g. 10.4
            $table->string('network', 24)->nullable();         // visa|mastercard

            $table->string('status', 32)->default('needs_response');
            // warning_needs_response|needs_response|under_review|won|lost|accepted|expired|charge_refunded

            $table->boolean('is_charge_refundable')->default(false);
            $table->boolean('funds_withdrawn')->default(false);  // have we debited the merchant yet
            $table->boolean('funds_reinstated')->default(false);

            $table->foreignId('payment_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference')->nullable();
            $table->string('acquirer_reference')->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('evidence_due_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
            $table->index('evidence_due_by');
            $table->index('payment_id');
        });

        Schema::create('dispute_evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('field', 64);   // receipt|shipping_documentation|customer_communication|refund_policy|service_date
            $table->text('text_value')->nullable();
            $table->string('disk', 32)->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->boolean('is_submitted')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['dispute_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_evidence');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('refunds');
    }
};
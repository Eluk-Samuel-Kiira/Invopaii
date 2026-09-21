<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Canonical list of event types. Seed this; the dashboard reads it to build
        // the "events to send" checkbox list.
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 96)->unique();   // payment.succeeded
            $table->string('resource', 48);         // payment
            $table->string('description')->nullable();
            $table->string('category', 48)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // The immutable record of something that happened. Webhooks are a delivery
        // mechanism for these — the event exists whether or not anyone is listening.
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // evt_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('type', 96);                  // payment.succeeded
            $table->string('resource_type')->nullable(); // App\Models\Payment
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('resource_public_id', 40)->nullable();

            $table->json('data');                        // serialised object at time of event
            $table->json('previous_attributes')->nullable(); // for *.updated events
            $table->string('api_version', 16)->nullable();

            $table->string('origin', 32)->default('api'); // api|dashboard|system|provider_webhook
            $table->foreignId('triggered_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedSmallInteger('pending_webhooks')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['company_id', 'mode', 'type', 'created_at'], 'events_company_mode_type_created_index');
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('public_id', 40)->unique();   // whe_xxx
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('url', 2048);
            $table->string('description')->nullable();
            $table->string('secret');                    // whsec_xxx, encrypted; used for HMAC signature
            $table->json('enabled_events');              // ["*"] or ["payment.succeeded","refund.created"]
            $table->string('api_version', 16)->nullable();

            $table->string('status', 24)->default('enabled'); // enabled|disabled|auto_disabled
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->text('disabled_reason')->nullable();

            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->unsignedSmallInteger('max_attempts')->default(8);
            $table->json('custom_headers')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'mode', 'status']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);

            $table->string('event_type', 96);
            $table->string('status', 24)->default('pending'); // pending|delivering|succeeded|failed|abandoned
            $table->unsignedSmallInteger('attempt')->default(0);

            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();      // truncate before storing
            $table->json('response_headers')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_class', 128)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->boolean('is_manual_retry')->default(false);

            $table->timestamps();

            $table->index(['status', 'next_retry_at']);
            $table->index(['company_id', 'mode', 'created_at']);
            $table->index(['event_id', 'webhook_endpoint_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
    }
};
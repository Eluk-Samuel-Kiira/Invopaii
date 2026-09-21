<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Secret keys are stored hashed. The plaintext is shown exactly once,
        // at creation. key_prefix lets you look the row up without a full scan.
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name')->nullable();          // "Production server", "Zapier"
            $table->string('mode', 8);                   // test|live
            $table->string('type', 16);                  // publishable|secret|restricted
            $table->string('key_prefix', 24)->index();   // sk_test_ / pk_live_
            $table->string('key_hash', 128)->unique();   // hash('sha256', $plaintext)
            $table->string('last_four', 8);

            $table->json('scopes')->nullable();          // restricted keys: ["payments:read","refunds:write"]
            $table->json('allowed_ips')->nullable();     // CIDR allowlist
            $table->unsignedInteger('rate_limit_per_minute')->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'mode', 'type']);
            $table->index('revoked_at');
        });

        // Guarantees that a retried POST never double-charges.
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8);
            $table->string('key', 255);
            $table->string('method', 10);
            $table->string('endpoint');
            $table->string('request_hash', 128);          // reject same key + different body
            $table->string('status', 16)->default('in_progress'); // in_progress|completed|failed
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_type')->nullable();  // created object, for quick replay
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'mode', 'key'], 'idempotency_company_mode_key_unique');
            $table->index('expires_at');
        });

        // Powers the "Logs" tab in the dashboard. Prune on a schedule.
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('mode', 8)->nullable();

            $table->string('method', 10);
            $table->string('path');
            $table->string('route_name')->nullable();
            $table->string('api_version', 16)->nullable();
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->string('request_id', 64)->index();

            $table->json('request_headers')->nullable();  // redact authorization
            $table->json('request_body')->nullable();     // redact PAN / CVV
            $table->json('response_body')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();

            $table->string('resource_type')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'mode', 'created_at']);
            $table->index(['status_code', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('api_keys');
    }
};
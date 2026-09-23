<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who did what, from where. Never delete rows from this table.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('mode', 8)->nullable();

            $table->string('action', 96);      // payment.refunded, api_key.revoked, member.invited
            $table->string('actor_type', 24)->default('user'); // user|api|system|support
            $table->string('actor_label')->nullable();          // email or key label, kept even if deleted
            $table->nullableMorphs('auditable');
            $table->string('resource_public_id', 40)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id', 64)->nullable();
            $table->boolean('is_sensitive')->default(false); // shown with a warning badge in the UI

            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // Laravel's database notification channel, plus a company scope.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category', 48)->nullable(); // payment|payout|dispute|compliance|security|product
            $table->string('severity', 16)->default('info'); // info|success|warning|critical
            $table->text('data');
            $table->string('action_url', 2048)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'read_at']);
        });

        // Per-user, per-company channel preferences.
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('event_category', 48);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->string('digest_frequency', 16)->default('instant'); // instant|daily|weekly|off
            $table->timestamps();

            $table->unique(['user_id', 'company_id', 'event_category'], 'notif_prefs_unique');
        });

        // Async CSV / PDF generation for reports the merchant downloads.
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mode', 8);

            $table->string('type', 48);   // payments|payouts|invoices|customers|balance_transactions|disputes
            $table->string('format', 16)->default('csv'); // csv|xlsx|pdf
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();

            $table->string('status', 24)->default('queued'); // queued|processing|completed|failed|expired
            $table->unsignedInteger('row_count')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('disk', 32)->nullable();
            $table->string('path')->nullable();
            $table->string('download_token', 80)->nullable()->unique();
            $table->unsignedSmallInteger('progress_percent')->default(0);
            $table->text('failure_reason')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'mode', 'status']);
        });

        // Free-form per-company key/value config that doesn't deserve a column.
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 8)->nullable();   // null = applies to both modes
            $table->string('group', 48);             // checkout|receipts|branding|security|payouts
            $table->string('key', 96);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'mode', 'group', 'key'], 'company_settings_unique');
        });

        // Product announcements and incident banners shown in the dashboard.
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('body');
            $table->string('type', 24)->default('product'); // product|incident|maintenance|policy
            $table->string('severity', 16)->default('info');
            $table->string('action_label', 64)->nullable();
            $table->string('action_url', 2048)->nullable();
            $table->json('target_countries')->nullable();
            $table->json('target_company_ids')->nullable();
            $table->boolean('is_dismissible')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'starts_at', 'ends_at']);
        });

        Schema::create('announcement_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('dismissed_at')->useCurrent();

            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_dismissals');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('exports');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
    }
};
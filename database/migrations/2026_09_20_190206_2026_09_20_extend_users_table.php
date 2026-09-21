<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Company context
            $table->foreignId('current_company_id')->nullable()->after('role_id')
                ->constrained('companies')->nullOnDelete();
            $table->string('current_mode', 8)->default('test')->after('current_company_id');

            // Two-factor
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('two_factor_method', 16)->nullable()->after('two_factor_confirmed_at');

            // Security posture
            $table->timestamp('password_changed_at')->nullable();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('is_platform_admin')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('timezone', 64)->nullable();

            $table->index('current_company_id');
            $table->index('is_platform_admin');
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name')->nullable();
            $table->string('device_fingerprint', 128)->index();
            $table->string('platform', 48)->nullable();
            $table->string('browser', 48)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint'], 'user_devices_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_company_id']);
            $table->dropColumn([
                'current_company_id', 'current_mode',
                'two_factor_secret', 'two_factor_recovery_codes',
                'two_factor_confirmed_at', 'two_factor_method',
                'password_changed_at', 'failed_login_attempts', 'locked_until',
                'last_login_ip', 'is_platform_admin', 'terms_accepted_at',
                'locale', 'timezone',
            ]);
        });
    }
};
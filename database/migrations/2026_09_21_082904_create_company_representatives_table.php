<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Directors, signatories and ultimate beneficial owners.
        Schema::create('company_representatives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 2)->nullable();     // ISO2
            $table->string('job_title')->nullable();

            $table->boolean('is_director')->default(false);
            $table->boolean('is_owner')->default(false);       // UBO
            $table->boolean('is_signatory')->default(false);
            $table->boolean('is_primary_contact')->default(false);
            $table->decimal('ownership_percent', 5, 2)->nullable();

            // Identity document — the number itself must be encrypted at the model layer.
            $table->string('id_document_type', 32)->nullable(); // passport|national_id|drivers_license
            $table->text('id_document_number')->nullable();     // encrypted cast
            $table->string('id_document_country', 2)->nullable();
            $table->date('id_document_expires_on')->nullable();

            $table->string('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('country_code', 2)->nullable();

            $table->string('kyc_status', 24)->default('unverified'); // unverified|pending|verified|rejected
            $table->boolean('pep_check_passed')->nullable();          // politically exposed person
            $table->boolean('sanctions_check_passed')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'kyc_status']);
        });

        Schema::create('company_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_representative_id')->nullable()
                ->constrained('company_representatives')->cascadeOnDelete();

            $table->string('type', 64);   // certificate_of_incorporation|tax_certificate|bank_statement|utility_bill|id_front|id_back|selfie|memarts
            $table->string('original_filename')->nullable();
            $table->string('disk', 32)->default('s3');
            $table->string('path');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 128)->nullable();

            $table->string('status', 24)->default('pending'); // pending|approved|rejected|expired
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->date('expires_on')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'type', 'status']);
        });

        // Every automated or manual compliance check we run, kept for audit.
        Schema::create('verification_checks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('checkable');     // company or company_representative
            $table->string('type', 64);      // kyb_registry|aml_screening|sanctions|pep|document_ocr|liveness|bank_account
            $table->string('provider', 64)->nullable(); // smile_id, dojah, complyadvantage
            $table->string('provider_reference')->nullable();
            $table->string('status', 24)->default('pending'); // pending|passed|failed|manual_review|errored
            $table->unsignedSmallInteger('score')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_checks');
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('company_representatives');
    }
};
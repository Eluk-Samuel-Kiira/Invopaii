<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->unique();
            $table->string('name');
            $table->string('official_name')->nullable();
            $table->string('phone_code', 8)->nullable();
            $table->string('default_currency', 3)->nullable();
            $table->string('region', 64)->nullable();
            $table->string('subregion', 64)->nullable();
            $table->string('flag_emoji', 16)->nullable();

            // Stardena market posture
            $table->boolean('is_supported')->default(false);
            $table->boolean('collections_enabled')->default(false);
            $table->boolean('payouts_enabled')->default(false);
            $table->boolean('is_high_risk')->default(false);
            $table->boolean('is_sanctioned')->default(false);

            // Compliance / capability JSON
            $table->json('required_business_documents')->nullable();
            $table->json('supported_payment_methods')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['is_supported', 'collections_enabled']);
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
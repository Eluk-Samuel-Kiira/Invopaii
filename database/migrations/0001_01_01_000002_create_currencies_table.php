<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8)->nullable();
            $table->unsignedTinyInteger('exponent')->default(2);
            $table->boolean('is_zero_decimal')->default(false);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_settlement_currency')->default(false);
            $table->boolean('is_presentment_currency')->default(true);

            $table->unsignedBigInteger('min_charge_amount')->nullable();
            $table->unsignedBigInteger('max_charge_amount')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'is_settlement_currency']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 24, 12);
            $table->decimal('markup_percent', 8, 4)->default(0);
            $table->decimal('effective_rate', 24, 12);
            $table->string('provider', 64)->nullable();
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency', 'effective_from'], 'fx_pair_effective_unique');
            $table->index(['base_currency', 'quote_currency', 'effective_to']);
            $table->index('effective_from');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_special_tariff_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_special_tariff_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->decimal('charge', 12, 2);
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->unsignedInteger('transit_days')->nullable();
            $table->timestamps();

            $table->unique(['client_special_tariff_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_special_tariff_zone_prices');
    }
};

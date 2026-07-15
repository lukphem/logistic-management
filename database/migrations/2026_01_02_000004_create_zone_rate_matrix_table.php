<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_rate_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('destination_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->unique(['rate_card_id', 'origin_zone_id', 'destination_zone_id'], 'zone_matrix_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_rate_matrix');
    }
};

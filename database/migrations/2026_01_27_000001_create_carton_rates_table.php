<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correction: carton_rate previously priced at a single flat
     * quantity × rate, ignoring both carton size and zone entirely —
     * not what was actually asked for ("carton rate (small carton, big,
     * medium...) will use zone too, with pieces as multiplier"). This
     * table is the real implementation: one row per (rate card, zone,
     * carton size), matching the same "one rate card owns its own rate
     * table" pattern as zone_weight_rates.
     */
    public function up(): void
    {
        Schema::create('carton_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->enum('carton_size', ['small', 'medium', 'large']);
            $table->decimal('price_per_carton', 12, 2);
            $table->timestamps();

            $table->unique(['rate_card_id', 'zone_id', 'carton_size'], 'carton_rate_unique');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('carton_size', ['small', 'medium', 'large'])->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carton_rates');

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('carton_size');
        });
    }
};

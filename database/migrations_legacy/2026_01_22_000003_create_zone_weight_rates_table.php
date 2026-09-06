<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the 'origin_destination_weight' billing model: From Zone,
     * To Zone, weight band, and service type together resolve to a
     * price, transit time, and an overage rate for weight beyond the
     * band's max. A shipment's own from_zone/to_zone are resolved via
     * zone_mappings (each city → one zone), so this table only needs one
     * row per (zone pair, weight band, service type) combination —
     * not one per city pair.
     */
    public function up(): void
    {
        Schema::create('zone_weight_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('to_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->decimal('min_weight', 8, 2);
            $table->decimal('max_weight', 8, 2);
            $table->string('service_type');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('transit_days')->nullable();
            $table->decimal('extra_amount_per_extra_kg', 10, 2)->default(0); // charged per kg beyond max_weight

            $table->timestamps();

            $table->unique(['rate_card_id', 'from_zone_id', 'to_zone_id', 'min_weight', 'max_weight', 'service_type'], 'zone_weight_rate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_weight_rates');
    }
};

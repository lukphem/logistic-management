<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assigns a city to a Zone — e.g. "Port Harcourt = Zone 2". A city
     * has exactly one zone (unique constraint), so a shipment's origin
     * and destination zones can each be resolved with one lookup, and
     * the origin_destination_weight rate table (zone_weight_rates) can
     * then look up pricing by (from_zone, to_zone) without needing an
     * entry for every possible city PAIR — only every city needs mapping
     * once, and every route between two mapped cities resolves
     * automatically.
     */
    public function up(): void
    {
        Schema::create('zone_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_mappings');
    }
};

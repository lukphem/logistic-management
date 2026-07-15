<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zones group areas (cities/regions) for zone-to-zone rate matrices
     * and rider/geofence assignment. A hub may serve multiple zones.
     */
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Lagos Mainland", "Abuja Central"
            $table->string('code')->unique(); // e.g. LAG-M
            $table->foreignId('hub_id')->nullable()->constrained()->nullOnDelete();
            $table->json('geofence')->nullable(); // GeoJSON polygon, optional
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};

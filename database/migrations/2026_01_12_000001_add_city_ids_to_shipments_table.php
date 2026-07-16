<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive to origin_zone_id/destination_zone_id (Increment 2) — Zone
     * stays the actual rate-calculation key for the zone_to_zone billing
     * model. City is what the client-facing quote/booking forms actually
     * let someone pick (a real place name is far more usable than
     * choosing a "zone" the client has no concept of), populated from the
     * Country/State/City data set up in Setups → Location.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('origin_city_id')->nullable()->after('origin_zone_id')->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->after('destination_zone_id')->constrained('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_city_id');
            $table->dropConstrainedForeignId('destination_city_id');
        });
    }
};

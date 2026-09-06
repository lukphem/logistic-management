<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capacity belongs to the VEHICLE, not a lane rate — a tariff's own
     * Max weight limit is validated against this (FleetBillingTariffController),
     * kept editable rather than locked, so a specific lane can still be
     * more restrictive (e.g. a bridge weight limit) than the vehicle's
     * full capacity.
     *
     * Volumetric capacity is the vehicle's own cargo dimensions —
     * separate from a shipment's own volumetric weight calculation
     * (PricingEngine::resolveChargeableWeight()), which is about
     * pricing; this is about whether the cargo physically fits.
     *
     * Haul rate, distance, and the minimum-trip floor are removed from
     * Fleet Billing entirely, per explicit direction — Weight Charge is
     * now the only component of freight.
     */
    public function up(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->decimal('max_weight_capacity', 10, 2)->nullable()->after('code');
            $table->decimal('max_length_cm', 10, 2)->nullable()->after('max_weight_capacity');
            $table->decimal('max_width_cm', 10, 2)->nullable()->after('max_length_cm');
            $table->decimal('max_height_cm', 10, 2)->nullable()->after('max_width_cm');
            $table->boolean('is_open_body')->default(false)->after('max_height_cm');
        });

        Schema::table('fleet_billing_tariffs', function (Blueprint $table) {
            $table->dropColumn(['base_haul_rate', 'minimum_trip_charge', 'distance_km', 'distance_rate_per_km']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_types', function (Blueprint $table) {
            $table->dropColumn(['max_weight_capacity', 'max_length_cm', 'max_width_cm', 'max_height_cm', 'is_open_body']);
        });

        Schema::table('fleet_billing_tariffs', function (Blueprint $table) {
            $table->decimal('base_haul_rate', 12, 2)->default(0);
            $table->decimal('minimum_trip_charge', 12, 2)->default(0);
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('distance_rate_per_km', 10, 2)->default(0);
        });
    }
};

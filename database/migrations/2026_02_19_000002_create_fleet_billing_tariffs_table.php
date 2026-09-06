<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A third billing model, following the industry-standard cost-based
     * freight rating formula given directly:
     *
     *   freight = base_haul_rate + weight_charge + distance_charge
     *   freight = max(freight, minimum_trip_charge)      -- floor
     *   fuel_surcharge = freight × fuel_surcharge_percentage
     *   empty_return = flat or % of freight, only when chosen at
     *                  booking/quote time (not part of this formula —
     *                  see PricingEngine::fleetBilling())
     *
     * Route/lane matching reuses OriginDestinationTariff's exact shape
     * — origin/destination each independently a Nigeria state (+
     * optional city) or a country. Weight banding reuses the same
     * three-field shape as Standard Billing / Origin to Destination:
     * min_weight/max_weight/max_weight_limit, where max_weight_limit is
     * the band's matching boundary and max_weight is the overage
     * reference (Increment 87's corrected definitions) —
     * additional_weight/additional_charge apply on top of base_charge
     * (the weight-band's own base, separate from base_haul_rate).
     *
     * distance_km is configured per lane (a known route has a known
     * distance), not re-entered per shipment.
     */
    public function up(): void
    {
        Schema::create('fleet_billing_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained()->cascadeOnDelete();

            $table->foreignId('origin_state_id')->nullable()->constrained('states')->cascadeOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('origin_country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('destination_state_id')->nullable()->constrained('states')->cascadeOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('destination_country_id')->nullable()->constrained('countries')->cascadeOnDelete();

            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_weight_limit', 10, 2);
            $table->decimal('base_charge', 12, 2)->default(0);
            $table->decimal('additional_weight', 10, 2)->default(1);
            $table->decimal('additional_charge', 12, 2)->default(0);

            $table->decimal('base_haul_rate', 12, 2)->default(0);
            $table->decimal('minimum_trip_charge', 12, 2)->default(0);
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('distance_rate_per_km', 10, 2)->default(0);
            $table->decimal('fuel_surcharge_percentage', 5, 2)->default(0);

            $table->enum('empty_return_charge_type', ['flat', 'percentage'])->default('flat');
            $table->decimal('empty_return_charge_value', 12, 2)->default(0);

            $table->unsignedInteger('transit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_billing_tariffs');
    }
};

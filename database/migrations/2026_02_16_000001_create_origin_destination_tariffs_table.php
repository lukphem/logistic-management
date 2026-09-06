<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A second, genuinely different billing model from Standard Billing
     * — a direct origin-to-destination route lookup, no Zone/
     * ZoneMapping involved at all. Each row prices one specific route
     * (state-to-state, or a specific city within a state on either
     * side) directly.
     *
     * origin_city_id / destination_city_id are nullable — null means
     * "this rate applies state-wide", a specific city means "this rate
     * applies to this city specifically, overriding the state-wide rate
     * for that city". PricingEngine picks the most specific match
     * available for a given shipment (see resolveOriginDestinationTariff()).
     *
     * base_charge/additional_charge live directly on this table, unlike
     * Standard Billing's separate TariffZonePrice — there's no zone
     * indirection here for a second table to represent.
     *
     * max_weight_limit — same independently-settable overage reference
     * as Standard Billing gained in the previous migration, required
     * from the start here rather than retrofitted.
     */
    public function up(): void
    {
        Schema::create('origin_destination_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('destination_state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_weight_limit', 10, 2);
            $table->decimal('base_charge', 12, 2);
            $table->decimal('additional_weight', 10, 2)->default(1);
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->unsignedInteger('transit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origin_destination_tariffs');
    }
};

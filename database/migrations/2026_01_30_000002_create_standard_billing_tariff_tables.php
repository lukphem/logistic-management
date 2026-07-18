<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A weight band for a given service type. additional_weight/
     * max_weight (used as both the band's upper bound AND the overage
     * threshold, per the earlier agreed simplification) drive the
     * overage formula: any weight above max_weight is charged in
     * additional_weight-sized increments, at whatever
     * tariff_zone_prices.additional_charge applies for the resolved
     * zone.
     */
    public function up(): void
    {
        Schema::create('standard_billing_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('additional_weight', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // One row per (tariff, zone) — however many zones a business
        // actually has. Never a fixed zone1..zoneN set of columns, so
        // adding a 5th or 6th zone later needs zero schema changes.
        Schema::create('tariff_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained('standard_billing_tariffs')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->decimal('charge', 12, 2);
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->unsignedInteger('transit_days')->nullable();
            $table->timestamps();

            $table->unique(['tariff_id', 'zone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_zone_prices');
        Schema::dropIfExists('standard_billing_tariffs');
    }
};

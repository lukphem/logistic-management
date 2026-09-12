<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors fleet_billing_tariffs' own structure exactly, scoped to
     * one client account — same relationship client_special_tariffs
     * has to standard_billing_tariffs, and
     * client_origin_destination_tariffs has to origin_destination_tariffs.
     * PricingEngine checks for a matching row here before falling back
     * to the shared fleet_billing_tariffs table.
     */
    public function up(): void
    {
        Schema::create('client_fleet_billing_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
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
            $table->decimal('base_charge', 12, 2)->default(0.00);
            $table->decimal('additional_weight', 10, 2)->default(1.00);
            $table->decimal('additional_charge', 12, 2)->default(0.00);
            $table->decimal('fuel_surcharge_percentage', 5, 2)->default(0.00);
            $table->enum('empty_return_charge_type', ['flat', 'percentage'])->default('flat');
            $table->decimal('empty_return_charge_value', 12, 2)->default(0.00);
            $table->unsignedInteger('transit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_fleet_billing_tariffs');
    }
};

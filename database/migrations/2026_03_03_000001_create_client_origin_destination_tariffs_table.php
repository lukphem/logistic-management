<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors origin_destination_tariffs' own structure exactly, scoped
     * to one client account — a genuinely separate rate, not a discount
     * off the company one, same relationship the existing
     * client_special_tariffs has to standard_billing_tariffs.
     * PricingEngine checks for a matching row here before falling back
     * to the shared origin_destination_tariffs table, same
     * check-first-fall-through-silently pattern already proven for
     * Standard Billing.
     *
     * Explicit short names on the two longest foreign keys — one of
     * Laravel's auto-generated names for this table lands exactly at
     * MySQL's 64-character limit, too close to trust after the
     * client_special_tariff_zone_prices incident earlier in this
     * project.
     */
    public function up(): void
    {
        Schema::create('client_origin_destination_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_state_id')->nullable()->constrained('states')->cascadeOnDelete();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->cascadeOnDelete();
            $table->foreignId('origin_country_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('destination_state_id')->nullable();
            $table->foreignId('destination_city_id')->nullable();
            $table->foreignId('destination_country_id')->nullable();
            $table->decimal('min_weight', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_weight_limit', 10, 2);
            $table->decimal('base_charge', 12, 2);
            $table->decimal('additional_weight', 10, 2)->default(1.00);
            $table->decimal('additional_charge', 12, 2)->default(0.00);
            $table->unsignedInteger('transit_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('destination_state_id', 'codt_dest_state_foreign')
                ->references('id')->on('states')->cascadeOnDelete();
            $table->foreign('destination_city_id', 'codt_dest_city_foreign')
                ->references('id')->on('cities')->cascadeOnDelete();
            $table->foreign('destination_country_id', 'codt_dest_country_foreign')
                ->references('id')->on('countries')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_origin_destination_tariffs');
    }
};

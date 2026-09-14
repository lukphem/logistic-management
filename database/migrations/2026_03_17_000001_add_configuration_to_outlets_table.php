<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same "null/empty = unrestricted" shape used everywhere else in
     * this project for this kind of opt-out list
     * (ClientAccount::disabled_billing_models, Setting::supported_
     * billing_models) — an outlet that's never had this touched
     * behaves exactly as it always has, nothing newly blocked by
     * default.
     *
     * disabled_service_type_ids stores service_types.id values, not
     * codes — service types are a real, ID-keyed entity in this
     * system (Increment 43), so this follows the same convention
     * relationships elsewhere use rather than a fragile name/code
     * match.
     *
     * discount_percentage is flat, not per-service-type — deliberately
     * simpler than ClientAccount's discount mechanism (which has a
     * whole per-service-type table backing it), since this is a
     * single number applied to the standard tariff for a walk-in
     * shipment booked at this outlet, not a contracted client
     * relationship with per-service negotiated rates.
     */
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->boolean('can_collect_cash')->default(true)->after('is_active');
            $table->json('disabled_billing_models')->nullable()->after('can_collect_cash');
            $table->json('disabled_service_type_ids')->nullable()->after('disabled_billing_models');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('disabled_service_type_ids');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['can_collect_cash', 'disabled_billing_models', 'disabled_service_type_ids', 'discount_percentage']);
        });
    }
};

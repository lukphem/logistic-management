<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * origin_district_id/destination_district_id mirror the existing
     * city fields — optional, more granular than city. When set, the
     * district's onforwarding classification takes priority over the
     * city's for billing purposes (see ShipmentPricingService).
     *
     * onforwarding_amount is its own billing line, alongside the
     * existing base/surcharge/discount/insurance/vat breakdown — kept
     * separate so it's visible on its own in the shipment detail view,
     * not folded silently into "surcharges."
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('origin_district_id')->nullable()->after('origin_city_id')->constrained('districts')->nullOnDelete();
            $table->foreignId('destination_district_id')->nullable()->after('destination_city_id')->constrained('districts')->nullOnDelete();
            $table->decimal('onforwarding_amount', 12, 2)->default(0)->after('surcharge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_district_id');
            $table->dropConstrainedForeignId('destination_district_id');
            $table->dropColumn('onforwarding_amount');
        });
    }
};

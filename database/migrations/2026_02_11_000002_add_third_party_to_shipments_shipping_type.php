<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A third value alongside domestic/international — for a shipment
     * arranged entirely between two OTHER countries (see
     * ThirdPartyCountryMapping), where Nigeria isn't the origin or the
     * destination. Still never picked manually, same as
     * domestic/international — auto-derived by PricingEngine at pricing
     * time from which resolution path actually fired.
     *
     * Drop-and-recreate rather than ->change() on the enum column, same
     * reliability reason as Increment 75's service_types.route_type
     * change — existing values preserved and restored explicitly.
     */
    public function up(): void
    {
        $existing = DB::table('shipments')->pluck('shipping_type', 'id');

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('shipping_type');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('shipping_type', ['domestic', 'international', 'third_party'])->nullable()->after('service_type_id');
        });

        foreach ($existing as $id => $shippingType) {
            if ($shippingType !== null) {
                DB::table('shipments')->where('id', $id)->update(['shipping_type' => $shippingType]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('shipping_type');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('shipping_type', ['domestic', 'international'])->nullable()->after('service_type_id');
        });
    }
};

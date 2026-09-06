<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cross-Trade was a third route_type value, a sibling to domestic
     * and international — corrected here to be genuinely NESTED under
     * international instead, per explicit direction. It's now a third
     * trade_direction value (alongside import/export), since
     * trade_direction was already exclusively an international-only
     * field — Cross-Trade fits that shape naturally rather than needing
     * its own route_type slot.
     *
     * Any service type currently route_type='third_party' is migrated
     * to route_type='international', trade_direction='cross_trade' —
     * preserving which service types were actually set to it, not
     * silently losing that distinction. route_type then shrinks back to
     * just domestic/international.
     */
    public function up(): void
    {
        $existingTradeDirections = DB::table('service_types')->pluck('trade_direction', 'id');
        $thirdPartyIds = DB::table('service_types')->where('route_type', 'third_party')->pluck('id');

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('trade_direction');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('trade_direction', ['import', 'export', 'cross_trade'])->nullable()->after('route_type');
        });

        foreach ($existingTradeDirections as $id => $direction) {
            if (in_array($direction, ['import', 'export'])) {
                DB::table('service_types')->where('id', $id)->update(['trade_direction' => $direction]);
            }
        }

        DB::table('service_types')->whereIn('id', $thirdPartyIds)->update([
            'route_type' => 'international',
            'trade_direction' => 'cross_trade',
        ]);

        $existingRouteTypes = DB::table('service_types')->pluck('route_type', 'id');

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international'])->default('domestic')->after('billing_model');
        });

        foreach ($existingRouteTypes as $id => $routeType) {
            DB::table('service_types')->where('id', $id)->update([
                'route_type' => in_array($routeType, ['domestic', 'international']) ? $routeType : 'domestic',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international', 'third_party'])->default('domestic')->after('billing_model');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('trade_direction');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('trade_direction', ['import', 'export'])->nullable()->after('route_type');
        });
    }
};

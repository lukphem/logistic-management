<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A third route_type value for a service type used exclusively for
     * third-party arrangements (shipments between two other countries,
     * not touching Nigeria — see ThirdPartyCountryMapping). Still
     * required, same as domestic/international — every service type is
     * exactly one of the three, never blank.
     *
     * Drop-and-recreate rather than ->change(), same reliability reason
     * as the previous two enum changes on this table.
     */
    public function up(): void
    {
        $existing = DB::table('service_types')->pluck('route_type', 'id');

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international', 'third_party'])->default('domestic')->after('billing_model');
        });

        foreach ($existing as $id => $routeType) {
            DB::table('service_types')->where('id', $id)->update([
                'route_type' => in_array($routeType, ['domestic', 'international', 'third_party']) ? $routeType : 'domestic',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international'])->default('domestic')->after('billing_model');
        });
    }
};

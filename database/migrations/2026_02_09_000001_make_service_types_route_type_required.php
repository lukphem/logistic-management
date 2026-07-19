<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * route_type was nullable, with null meaning "offered for both" —
     * now required, every service type is explicitly one or the other.
     * Existing rows with no route_type default to 'domestic', the most
     * likely case given how this system has been used so far — flip any
     * that should actually be international from the Service Types
     * screen after this runs.
     *
     * Drop-and-recreate rather than ->change() on the enum column —
     * doctrine/dbal's handling of MySQL enum modification is unreliable
     * across versions; dropping and adding fresh is the portable way to
     * change an enum's nullability/default. Every row's CURRENT value is
     * read before the drop and restored after, so this only affects
     * rows that were actually null — it must not silently reset rows
     * that were already correctly 'international'.
     */
    public function up(): void
    {
        $existing = DB::table('service_types')->pluck('route_type', 'id');

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international'])->default('domestic')->after('billing_model');
        });

        foreach ($existing as $id => $routeType) {
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
            $table->enum('route_type', ['domestic', 'international'])->nullable()->after('billing_model');
        });
    }
};

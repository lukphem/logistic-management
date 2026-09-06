<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Only meaningful for route_type = 'international' — a domestic
     * service type has no import/export direction at all, so this stays
     * nullable rather than forcing every domestic service type to carry
     * a meaningless value. An international service type's direction
     * determines which side of the route is Nigeria: export = Nigeria
     * origin, import = Nigeria destination.
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('trade_direction', ['import', 'export'])->nullable()->after('route_type');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('trade_direction');
        });
    }
};

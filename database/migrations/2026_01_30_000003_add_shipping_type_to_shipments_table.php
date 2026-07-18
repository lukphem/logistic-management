<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auto-derived at pricing time from which zone-resolution system
     * fired — domestic (ZoneMapping, state-pairs) or international
     * (ZoneCountryMapping, countries). Never a field anyone picks
     * manually; exists purely so reports/analytics can break shipments
     * down by domestic vs international without re-deriving it from
     * origin/destination every time.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('shipping_type', ['domestic', 'international'])->nullable()->after('service_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('shipping_type');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which route type this service is actually offered for — e.g.
     * "International Express" might only make sense for international
     * routes, while "Standard" applies to both. Nullable = applies to
     * both route types (the default, backward compatible).
     *
     * Distinct from shipments.shipping_type (Increment 44), which is
     * never manually picked — that's auto-derived per shipment from
     * whichever zone-mapping system resolved the route. This is the
     * opposite kind of field: a deliberate restriction on the SERVICE
     * TYPE's own definition, configured once, not per shipment.
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->enum('route_type', ['domestic', 'international'])->nullable()->after('billing_model');
        });
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('route_type');
        });
    }
};

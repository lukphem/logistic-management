<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A shipment created via a Test-mode API key is real enough to
     * exercise an integrator's own code end-to-end (it gets a real
     * tracking number, a real price breakdown, can be tracked and
     * cancelled) but is NOT a real, billable, operational shipment —
     * it never reaches a rider's real workflow and never appears on
     * an invoice. is_test is how every one of those exclusions is
     * expressed, rather than maintaining a second, parallel table.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('api_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });
    }
};

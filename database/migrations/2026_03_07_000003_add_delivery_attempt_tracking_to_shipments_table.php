<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * delivery_attempts_count increments once per scan event whose
     * status is marked is_delivery_attempt (RiderController::scan()).
     * delivery_attempts_exceeded_at is set the moment the count first
     * passes the shipment's client account's own
     * maximum_delivery_attempts — a timestamp rather than a boolean so
     * staff can see when it happened, not just that it did. Left null
     * for a shipment whose client account has no maximum configured,
     * since there's nothing to exceed.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('delivery_attempts_count')->default(0)->after('current_status');
            $table->timestamp('delivery_attempts_exceeded_at')->nullable()->after('delivery_attempts_count');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['delivery_attempts_count', 'delivery_attempts_exceeded_at']);
        });
    }
};

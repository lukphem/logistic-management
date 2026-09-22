<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * origin_hub_id/origin_outlet_id record which facility the batch
     * was booked from — but that isn't necessarily where the
     * shipments are actually picked up. Any user can be arranging
     * pickup from a remote town while booking through their own hub,
     * so the batch now also records an explicit origin city — this,
     * not the hub's own city, is what's actually used for pricing
     * and as the shipments' real origin.
     */
    public function up(): void
    {
        Schema::table('bulk_shipment_batches', function (Blueprint $table) {
            $table->foreignId('origin_city_id')->nullable()->after('sender_email')->constrained('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bulk_shipment_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_city_id');
        });
    }
};

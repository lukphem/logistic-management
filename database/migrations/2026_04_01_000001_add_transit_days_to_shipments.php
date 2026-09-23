<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * promised_delivery_at used to be calculated straight from the
     * quote at booking time — but a shipment that's only been booked,
     * not yet picked up or dropped off, is still outside the
     * company's possession entirely; the SLA clock starting before
     * that point was never accurate. transit_days preserves what the
     * quote actually promised so it can still be applied once the
     * shipment genuinely enters custody (the first Pickup or Drop-off
     * scan), rather than losing that number the moment the shipment
     * is created.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('transit_days')->nullable()->after('promised_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('transit_days');
        });
    }
};

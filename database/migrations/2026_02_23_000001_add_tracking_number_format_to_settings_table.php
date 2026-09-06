<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tracking_number_format holds a template like
     * "{service_code}-{origin_hub}{destination_hub}-{date:ymd}-{seq:5}"
     * — parsed token-by-token in Shipment::composeTrackingNumber().
     * Null/empty falls back to the original hardcoded
     * "{origin_hub}-{destination_hub}-" + date + random format, so an
     * existing deployment's numbering never silently changes just from
     * this column existing.
     *
     * next_tracking_sequence backs the {seq:N} token — a single running
     * counter (not reset daily/monthly) incremented atomically
     * (lockForUpdate) each time it's used, so two shipments booked in
     * the same second still get different numbers.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('tracking_number_format')->nullable()->after('quote_validity_days');
            $table->unsignedBigInteger('next_tracking_sequence')->default(1)->after('tracking_number_format');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['tracking_number_format', 'next_tracking_sequence']);
        });
    }
};

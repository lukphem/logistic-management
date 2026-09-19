<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Departure Scan needs to record who's actually carrying the
     * shipment out — a different person from handled_by (the staff
     * member who performed the scan at the counter). Separate from
     * shipment.assigned_rider_id (which this also updates, so the
     * shipment's own current assignment stays accurate) because the
     * audit trail needs to know who it was handed to at THIS specific
     * departure, even if the assignment changes again later.
     */
    public function up(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            $table->foreignId('handed_to_user_id')->nullable()->after('receiver_name')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scan_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handed_to_user_id');
        });
    }
};

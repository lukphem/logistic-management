<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Not every scan status should notify a customer — same reasoning
     * as is_delivery_attempt already being staff-configurable rather
     * than hardcoded: statuses themselves are fully staff-defined, so
     * which ones are milestone-worthy enough to email someone about
     * has to be a setting too, not an assumption baked into the scan
     * code. Booked/Out for Delivery/Delivered/Exception are seeded as
     * true (see ScanStatusSeeder) — the ones a real courier typically
     * notifies on — everything else defaults to false so adding a new
     * internal-only status later doesn't silently start emailing
     * customers about it.
     */
    public function up(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->boolean('notify_customer')->default(false)->after('is_delivery_attempt');
        });
    }

    public function down(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->dropColumn('notify_customer');
        });
    }
};

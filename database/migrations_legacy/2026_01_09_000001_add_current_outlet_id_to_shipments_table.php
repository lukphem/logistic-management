<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * current_hub_id (added in Increment 2) stays as the owning/routing
     * hub. current_outlet_id is additional and more specific: set when
     * the shipment is physically sitting at one of that hub's outlets
     * (e.g. picked up at an agent counter, or out for delivery staged
     * from an outlet). Null means "at the hub itself, not a sub-outlet."
     *
     * Both are updated together by RiderController::scan — see that
     * controller for how a scan at an outlet resolves and sets both.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('current_outlet_id')->nullable()->after('current_hub_id')->constrained('outlets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_outlet_id');
        });
    }
};

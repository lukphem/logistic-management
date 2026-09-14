<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pickup is an optional add-on staff can request per shipment,
     * same shape as insurance/COD — but whether it actually carries a
     * fee, and how much, depends on the booking account's own
     * is_pickup_chargeable/pickup_charge (Accounts tab, Billing &
     * Invoicing). pickup_amount is stored on the shipment itself
     * (like insurance_amount) rather than recalculated from the
     * account every time it's displayed, since the account's own
     * charge could change later — the shipment should keep showing
     * whatever it was actually charged at booking time.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->boolean('is_pickup_requested')->default(false)->after('is_cod');
            $table->decimal('pickup_amount', 12, 2)->default(0.00)->after('is_pickup_requested');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['is_pickup_requested', 'pickup_amount']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Managerial services were previously just access toggles
     * (warehouse_access, cod_enabled) with no associated charge — this
     * gives each one a fee, and adds Staff Management as a third
     * service (helping the client manage/pay their own staff) that
     * didn't exist as a concept before at all.
     *
     * cod_percentage is the company's cut of cash collected on the
     * client's behalf, not a discount-style fraction — stored as a
     * plain percentage (e.g. 2.5 meaning 2.5%), consistent with
     * vat_percentage elsewhere on this same table. warehouse_charge
     * and staff_management_charge are flat amounts, not percentages,
     * since neither scales off a cash-collection total the way COD's
     * does.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->decimal('cod_percentage', 5, 2)->nullable()->after('cod_enabled');
            $table->decimal('warehouse_charge', 10, 2)->nullable()->after('warehouse_access');
            $table->boolean('staff_management_enabled')->default(false)->after('warehouse_charge');
            $table->decimal('staff_management_charge', 10, 2)->nullable()->after('staff_management_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn(['cod_percentage', 'warehouse_charge', 'staff_management_enabled', 'staff_management_charge']);
        });
    }
};

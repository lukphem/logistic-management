<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An option's price can now be a flat amount OR a percentage
     * (applied to the shipment's base freight amount, same base VAT
     * already uses) — e.g. an "Acknowledgement" option priced as 2.5%
     * of freight rather than a fixed Naira figure. `price` stays the
     * single value column either way: a flat amount when charge_type is
     * 'flat', a percentage number (2.5 meaning 2.5%) when 'percentage'.
     * Existing options default to 'flat', preserving current behavior
     * exactly.
     */
    public function up(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->enum('charge_type', ['flat', 'percentage'])->default('flat')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->dropColumn('charge_type');
        });
    }
};

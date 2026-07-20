<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every additional service charge was always taxable — this makes
     * that configurable per option, defaulting to true so nothing
     * existing changes behavior. ShipmentPricingService now splits
     * additional-service totals into a vatable and non-vatable portion
     * before computing VAT, rather than taxing the combined total.
     */
    public function up(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->boolean('is_vatable')->default(true)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->dropColumn('is_vatable');
        });
    }
};

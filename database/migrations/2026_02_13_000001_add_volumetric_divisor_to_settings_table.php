<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Divides L×W×H (cm) to get volumetric weight (kg) — a fixed
     * company-wide policy (like VAT), not something that varies per
     * tariff or zone. 5000 is a common air-freight default; couriers
     * do use different values (4000, 5000, 6000 are all seen in
     * practice), so this is a setting, not a hardcoded constant.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedInteger('volumetric_divisor')->default(5000)->after('vat_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('volumetric_divisor');
        });
    }
};

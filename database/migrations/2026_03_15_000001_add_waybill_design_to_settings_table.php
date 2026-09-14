<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same pattern as label_design — one deployment-wide choice, not
     * per-shipment, since every Waybill a company issues should look
     * consistent.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('waybill_design', ['classic', 'modern', 'compact'])->default('classic')->after('waybill_terms');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('waybill_design');
        });
    }
};

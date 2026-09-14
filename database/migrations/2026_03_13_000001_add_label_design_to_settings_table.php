<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which of the three waybill layouts (Classic/Modern/Compact —
     * see WaybillController) prints when staff hit "Print Waybill" on
     * any shipment. One setting for the whole deployment, same as
     * waybill_thermal_size/waybill_show_qr right next to it — every
     * label a company prints should look the same, not vary shipment
     * to shipment.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('label_design', ['classic', 'modern', 'compact'])->default('classic')->after('waybill_show_qr');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('label_design');
        });
    }
};

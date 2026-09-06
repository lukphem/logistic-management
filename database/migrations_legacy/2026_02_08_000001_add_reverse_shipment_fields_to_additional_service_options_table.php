<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A third charge_type: 'percentage_of_reverse_shipment' — for
     * something like "Acknowledgement" (a signed document sent back to
     * origin, reverse logistics for paperwork). Its price isn't a
     * percentage of the outbound shipment's freight — it's a percentage
     * of what a SEPARATE mini reverse shipment would cost, priced
     * through the real pricing engine using its own weight and service
     * type. Both fields are only meaningful for that charge type;
     * nullable, since a flat or percentage-of-freight option never
     * touches them.
     */
    public function up(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->foreignId('reverse_service_type_id')->nullable()->after('charge_type')->constrained('service_types')->nullOnDelete();
            $table->decimal('reverse_weight_kg', 10, 2)->nullable()->after('reverse_service_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('additional_service_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverse_service_type_id');
            $table->dropColumn('reverse_weight_kg');
        });
    }
};

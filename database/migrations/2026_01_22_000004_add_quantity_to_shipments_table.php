<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic unit count — means "number of truckloads" for the
     * 'truckload' billing model, or "number of cartons" for
     * 'carton_rate'. One column reused for both since they're
     * structurally identical (quantity × rate), same way weight_kg is
     * reused across every weight-based model rather than having a
     * separate column per billing model.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->after('chargeable_weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};

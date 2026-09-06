<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fixes a real gap dating back to the original Increment 72: that
     * migration added reverse_service_type_id/reverse_weight_kg (the
     * columns 'percentage_of_reverse_shipment' needs) but never actually
     * widened the charge_type enum itself to include that third value —
     * so every attempt to save an Acknowledgement option with this
     * charge type has always failed with a truncation error at the DB
     * level, even though the model/controller/pricing logic all fully
     * support it.
     *
     * Raw SQL rather than Schema::table()->enum()->change() — widening
     * an existing enum via Blueprint's change() needs doctrine/dbal and
     * has a documented reliability history in this codebase (see
     * Increment 85's max_weight_limit migration note); a plain MODIFY
     * has no such dependency and is the standard, reliable way to widen
     * a MySQL enum.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE additional_service_options MODIFY charge_type ENUM('flat', 'percentage', 'percentage_of_reverse_shipment') NOT NULL DEFAULT 'flat'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE additional_service_options MODIFY charge_type ENUM('flat', 'percentage') NOT NULL DEFAULT 'flat'");
    }
};

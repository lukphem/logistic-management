<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A database-level guarantee, not just an application-level one —
     * PaymentController already generates a fresh reference per
     * attempt and never intentionally reuses one, but a unique
     * constraint is what actually makes "two payment attempts can
     * never share a reference" impossible rather than merely unlikely.
     * NULL is allowed and doesn't count toward uniqueness (standard
     * SQL behavior) — a shipment/settlement with no payment attempted
     * yet is fine to coexist with any number of others in the same
     * state.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->unique('payment_reference');
        });

        Schema::table('cash_settlements', function (Blueprint $table) {
            $table->unique('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
        });

        Schema::table('cash_settlements', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
        });
    }
};

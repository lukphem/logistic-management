<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinct from Outlet: a Unit is an organizational grouping of staff
     * WITHIN a hub (e.g. Operations, Customer Service, Dispatch,
     * Warehouse, Finance) — it has no physical address or GPS location of
     * its own and never affects shipment visibility. Outlet is a physical
     * sub-location; Unit is a team/department. A hub can have both,
     * independently.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};

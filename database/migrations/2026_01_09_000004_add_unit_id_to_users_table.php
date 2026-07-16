<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unlike hub_id/region_id/outlet_id, unit_id is NOT part of the
     * mutually-exclusive access scale — it's an independent, optional
     * organizational tag ("which team is this person on"). A hub- or
     * outlet-scoped user may optionally belong to a unit within that
     * hub; it never changes what shipments they can see.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('outlet_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};

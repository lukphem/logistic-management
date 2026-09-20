<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The units table, the Unit model, its admin CRUD, and the
     * unit_id field on the user form already existed — this migration
     * only adds the two columns that were still missing for any of
     * that to actually take effect: users.unit_id itself (the form
     * posted it, but there was no column to receive it) and
     * shipments.current_unit_id, which tracks a shipment's location
     * at this same finer grain alongside the existing
     * current_hub_id/current_outlet_id.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'unit_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->after('hub_id')->constrained('units')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('shipments', 'current_unit_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->foreignId('current_unit_id')->nullable()->after('current_hub_id')->constrained('units')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipments', 'current_unit_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('current_unit_id');
            });
        }

        if (Schema::hasColumn('users', 'unit_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unit_id');
            });
        }
    }
};

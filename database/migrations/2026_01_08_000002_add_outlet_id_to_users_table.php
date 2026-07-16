<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completes the access scale: Global > Region > Hub > Outlet. Exactly
     * one of region_id/hub_id/outlet_id is ever set — see
     * User::hasOutletAccess() and UserController::validateForm(), which
     * enforces the mutual exclusivity the same way it already does for
     * region_id/hub_id.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->after('hub_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
        });
    }
};

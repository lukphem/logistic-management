<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Never tracked before — nothing recorded which staff member
     * actually booked a given shipment. Added specifically for the
     * "Created By" report column; existing shipments will show blank
     * here since the data was never captured, only shipments created
     * from this point forward will have it.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('api_client_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};

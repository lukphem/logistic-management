<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The staff member who owns this client's relationship day to day
     * (an "account manager") - distinct from created_by, which is
     * whoever set the account up and never changes. business_manager_id
     * is meant to be reassigned over time as staffing changes, without
     * touching the historical record of who originally created it.
     */
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->foreignId('business_manager_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_manager_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * service_type was a free string on shipments, constrained only by
     * whatever happened to be in settings.service_names at the time —
     * no real referential integrity. Now a proper FK to service_types.
     * settings.service_names is dropped entirely — service_types (its
     * own CRUD screen under Setups) is now the single source of truth.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('service_type_id')->nullable()->after('api_client_id')->constrained()->nullOnDelete();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('service_names');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_type_id');
            $table->string('service_type')->nullable();
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->json('service_names')->nullable();
        });
    }
};

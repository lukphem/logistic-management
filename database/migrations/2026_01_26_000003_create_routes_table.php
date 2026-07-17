<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groups cities/districts into a delivery route — the foundation for
     * a FUTURE feature (automatic shipment sorting and driver/rider
     * allocation, not built yet). This increment only lays the data
     * model: Route entity + optional route_id on City and District. No
     * sorting or allocation logic exists yet.
     */
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('hub_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->after('operational_hub_id')->constrained()->nullOnDelete();
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('route_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('route_id');
        });

        Schema::dropIfExists('routes');
    }
};

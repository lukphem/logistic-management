<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->foreignId('territory_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->boolean('has_airport')->default(false)->after('territory_id');
        });
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropConstrainedForeignId('territory_id');
            $table->dropColumn('has_airport');
        });
    }
};

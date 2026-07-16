<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('onforwarding_classification_id')->nullable()->after('operational_hub_id')->constrained()->nullOnDelete();
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->foreignId('onforwarding_classification_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('onforwarding_classification_id');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('onforwarding_classification_id');
        });
    }
};

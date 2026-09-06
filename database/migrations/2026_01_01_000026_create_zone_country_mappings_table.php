<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_country_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_a_id')->nullable()->constrained('countries')->cascadeOnDelete();
            $table->foreignId('country_b_id')->constrained('countries')->cascadeOnDelete()->unique();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_country_mappings');
    }
};

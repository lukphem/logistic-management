<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zone_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_a_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('state_b_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->change();
            $table->timestamps();
            $table->unique(['state_a_id', 'state_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_mappings');
    }
};

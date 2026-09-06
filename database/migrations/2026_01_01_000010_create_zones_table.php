<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('applies_domestic')->default(false);
            $table->boolean('applies_international')->default(false);
            $table->enum('tier', ['A', 'B', 'C', 'D', 'E', 'F', 'international'])->nullable();
            $table->string('coverage_description')->nullable();
            $table->json('geofence')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};

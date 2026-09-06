<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('max_weight_capacity', 10, 2)->nullable();
            $table->decimal('max_length_cm', 10, 2)->nullable();
            $table->decimal('max_width_cm', 10, 2)->nullable();
            $table->decimal('max_height_cm', 10, 2)->nullable();
            $table->boolean('is_open_body')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};

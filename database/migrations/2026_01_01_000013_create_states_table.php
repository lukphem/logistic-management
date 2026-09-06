<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('has_airport')->default(false);
            $table->string('name');
            $table->string('short_code', 10)->nullable();
            $table->string('code', 10)->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};

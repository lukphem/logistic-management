<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bottom of the operating-location hierarchy: Country > State > City >
     * District/Area. code is auto-composed from the parent city's code —
     * {city->code}-{short_code}, e.g. "NG-LA-IKJ-GRA" — same pattern as
     * State and City.
     */
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_code', 10)->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};

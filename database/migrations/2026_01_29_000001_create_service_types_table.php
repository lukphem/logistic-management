<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces settings.service_names (a flat JSON array of free-typed
     * strings — "Express", "Economy" — with no real entity behind them)
     * with a proper, creatable table. Staff manage these under Setups
     * like everything else in the system, instead of a fixed list baked
     * into Company Settings.
     */
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('third_party_country_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_a_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('country_b_id')->constrained('countries')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            \$table->unique(['country_a_id', 'country_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('third_party_country_mappings');
    }
};

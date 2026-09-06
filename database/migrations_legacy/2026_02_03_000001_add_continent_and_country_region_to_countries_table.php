<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Named country_regions, not regions — a `regions` table already
     * exists (2026_01_07) for grouping Hubs/Users in the internal
     * access-scope hierarchy, which is a completely different concept
     * from geographic country groupings. Reusing that name/table would
     * have collided with an existing, unrelated feature.
     *
     * continent — a fixed, universally-agreed classification (Africa,
     * Europe, Asia, ...), so a plain string field, not an entity.
     *
     * country_regions — NOT fixed the same way; different sources group
     * regions differently, and this business might want proximity-based
     * groupings ("Bordering Nigeria") instead of standard geographic
     * ones ("West Africa"). Built like Territory: a real, staff-managed
     * entity, not a hardcoded list.
     */
    public function up(): void
    {
        Schema::create('country_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->string('continent')->nullable()->after('name');
            $table->foreignId('country_region_id')->nullable()->after('continent')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_region_id');
            $table->dropColumn('continent');
        });

        Schema::dropIfExists('country_regions');
    }
};

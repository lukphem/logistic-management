<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Confirmed unused anywhere else in the app (checked every
     * reference to zone->hub / hub_id before removing) — this was
     * scaffolded early on but the business isn't linking zones to a
     * specific hub, so it's dead schema, not a feature quietly relied
     * on elsewhere.
     */
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hub_id');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('hub_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};

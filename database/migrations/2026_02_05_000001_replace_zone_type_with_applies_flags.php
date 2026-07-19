<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Was a single type field (domestic XOR international) — a zone
     * could never be both, even though nothing about a zone's own
     * definition actually requires that split. Two independent booleans
     * instead: a zone can apply to domestic only, international only,
     * or both. At least one must be true (enforced in ZoneController's
     * validation, not the database — SQLite/MySQL check constraints
     * aren't worth the portability cost for this).
     */
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->boolean('applies_domestic')->default(false)->after('code');
            $table->boolean('applies_international')->default(false)->after('applies_domestic');
        });

        \Illuminate\Support\Facades\DB::table('zones')->where('type', 'domestic')->update(['applies_domestic' => true]);
        \Illuminate\Support\Facades\DB::table('zones')->where('type', 'international')->update(['applies_international' => true]);

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->string('type')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('zones')->where('applies_domestic', true)->update(['type' => 'domestic']);
        \Illuminate\Support\Facades\DB::table('zones')->where('applies_international', true)->where('applies_domestic', false)->update(['type' => 'international']);

        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['applies_domestic', 'applies_international']);
        });
    }
};

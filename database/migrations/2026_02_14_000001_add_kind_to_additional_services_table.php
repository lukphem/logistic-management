<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 'custom' (default) is today's free-form behavior — staff type a
     * name, add options, done. 'packaging' and 'acknowledgement' are
     * protected, seeded once here and never deletable/renamable from
     * the UI — every business using this system has both, they're not
     * something to accidentally lose or duplicate.
     *
     * Seeds both if they don't already exist by name — matched by
     * name rather than blindly inserting, so a business that already
     * created a "Packaging" or "Acknowledgement" service by hand before
     * this migration ran gets it upgraded to protected status instead
     * of ending up with a duplicate.
     */
    public function up(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->enum('kind', ['custom', 'packaging', 'acknowledgement'])->default('custom')->after('name');
        });

        $now = now();

        foreach (['Packaging' => 'packaging', 'Acknowledgement' => 'acknowledgement'] as $name => $kind) {
            $existing = DB::table('additional_services')->where('name', $name)->first();

            if ($existing) {
                DB::table('additional_services')->where('id', $existing->id)->update(['kind' => $kind]);
            } else {
                DB::table('additional_services')->insert([
                    'name' => $name,
                    'kind' => $kind,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};

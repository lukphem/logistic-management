<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renames country_id to country_b_id and adds country_a_id,
     * matching domestic ZoneMapping's state_a_id/state_b_id shape —
     * displayed the same way (two named columns) as requested.
     *
     * The underlying simplification is unchanged and still correct:
     * this business always ships FROM Nigeria, so country_a_id is
     * always Nigeria's id for every row, not a genuinely free pair the
     * way domestic's two states are. Adding the column explicitly
     * rather than leaving it implicit gives the same visual/structural
     * shape as domestic and leaves room to relax the "always Nigeria"
     * assumption later without another migration, without pretending
     * today's rows are anything other than Nigeria-vs-one-other-country.
     */
    public function up(): void
    {
        Schema::table('zone_country_mappings', function (Blueprint $table) {
            $table->renameColumn('country_id', 'country_b_id');
        });

        Schema::table('zone_country_mappings', function (Blueprint $table) {
            $table->foreignId('country_a_id')->nullable()->after('id')->constrained('countries')->cascadeOnDelete();
        });

        $nigeriaId = \App\Models\Country::where('code', 'NG')->value('id');

        if ($nigeriaId) {
            \Illuminate\Support\Facades\DB::table('zone_country_mappings')->update(['country_a_id' => $nigeriaId]);
        }
    }

    public function down(): void
    {
        Schema::table('zone_country_mappings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_a_id');
        });

        Schema::table('zone_country_mappings', function (Blueprint $table) {
            $table->renameColumn('country_b_id', 'country_id');
        });
    }
};

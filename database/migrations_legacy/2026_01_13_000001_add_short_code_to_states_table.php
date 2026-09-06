<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * short_code is what staff type (e.g. "LA" for Lagos). The existing
     * `code` column (added in Increment 17) becomes the auto-composed
     * full code — {country->code}-{short_code}, e.g. "NG-LA" — recomputed
     * automatically by State::booted() on every save. Client API calls
     * should reference `code`, not `short_code`.
     */
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->string('short_code', 10)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('states', function (Blueprint $table) {
            $table->dropColumn('short_code');
        });
    }
};

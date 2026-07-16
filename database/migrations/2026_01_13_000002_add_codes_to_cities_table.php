<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * code is auto-composed from the parent state's code —
     * {state->code}-{short_code}, e.g. "NG-LA-IKJ" for Ikeja — recomputed
     * automatically by City::booted() on every save.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('short_code', 10)->nullable()->after('name');
            $table->string('code')->nullable()->after('short_code');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['short_code', 'code']);
        });
    }
};

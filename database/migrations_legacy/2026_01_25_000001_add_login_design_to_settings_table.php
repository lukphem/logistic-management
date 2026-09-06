<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which of the login page's visual designs is active — see
     * resources/views/auth/designs/. Selectable from Company Settings so
     * each deployment can pick its own look without a code change.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('login_design')->default('route')->after('color_secondary');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('login_design');
        });
    }
};

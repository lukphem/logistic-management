<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same customizable-format approach as tracking_number_format —
     * account_number_format is nullable; null means the original
     * hardcoded default ({state}{outlet}{staff}{seq:5}) stays
     * unchanged, so an existing deployment's numbering never shifts
     * just from this column existing.
     *
     * allow_manual_account_number lets staff type an account number
     * by hand instead of the generated one (e.g. to carry over a
     * number from a previous system for an existing client) — off by
     * default, since letting every account number be hand-typed would
     * undermine the whole point of a consistent, location-derived
     * pattern.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('account_number_format')->nullable()->after('tracking_number_format');
            $table->boolean('allow_manual_account_number')->default(false)->after('account_number_format');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['account_number_format', 'allow_manual_account_number']);
        });
    }
};

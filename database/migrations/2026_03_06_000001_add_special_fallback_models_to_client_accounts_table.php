<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same JSON-array pattern as disabled_billing_models/
     * special_billing_models — for a billing model already in Special
     * mode, whether a shipment with no matching special rate should
     * fall back to the Standard rate (with its discount) instead of
     * being blocked outright. Absence means "never fall back" — the
     * safer default (fail closed, matching how Special mode's
     * blocking behavior already works today) rather than silently
     * allowing a fallback nobody explicitly asked for.
     *
     * Deliberately its own column rather than folded into
     * special_billing_models' existing shape — keeps "is this model in
     * Special mode" and "should Special fall back when it has no
     * answer" as two independent, additive facts, neither one
     * disturbing the other's existing meaning.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->json('special_fallback_models')->nullable()->after('special_billing_models');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropColumn('special_fallback_models');
        });
    }
};

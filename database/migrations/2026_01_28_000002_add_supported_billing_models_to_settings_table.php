<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which billing models this business actually uses — a subset of
     * Setting::BILLING_MODELS (the fixed catalog of known model types).
     * This is the ONLY billing-model-related thing that exists right
     * now: just a list. No calculation logic exists behind any of these
     * yet — each one gets built individually, discussed and configured
     * on its own, once picked from this list.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('supported_billing_models')->nullable()->after('login_design');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('supported_billing_models');
        });
    }
};

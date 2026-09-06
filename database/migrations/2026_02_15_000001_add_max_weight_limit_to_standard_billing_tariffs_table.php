<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bridging migration — reconciling a patch series applied out of
     * order against a repo state that never had the increment which
     * originally introduced this column. Independently settable from
     * min_weight/max_weight per the later Increment 87 correction;
     * null falls back to min_weight until backfilled.
     */
    public function up(): void
    {
        Schema::table('standard_billing_tariffs', function (Blueprint $table) {
            $table->decimal('max_weight_limit', 10, 2)->nullable()->after('max_weight');
        });
    }

    public function down(): void
    {
        Schema::table('standard_billing_tariffs', function (Blueprint $table) {
            $table->dropColumn('max_weight_limit');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hub::states() (Increment 22) lets more than one hub cover the same
     * state — fine for coverage, but ambiguous when Shipment needs to
     * resolve exactly ONE hub for a city whose state has multiple
     * covering hubs (it would otherwise just grab whichever comes first).
     * operational_hub_id is the explicit override: "this specific city,
     * regardless of how many hubs cover its state, is operationally
     * handled by THIS hub." Optional — only needed where that ambiguity
     * actually exists.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('operational_hub_id')->nullable()->after('state_id')->constrained('hubs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operational_hub_id');
        });
    }
};

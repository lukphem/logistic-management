<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * outlet_id replaces the old free-text express_center — outlets
     * are a real, existing concept (Outlet belongsTo Hub), and every
     * City already names its serving Hub (operational_hub_id), so
     * "which outlet serves this client" can now be a genuine dropdown
     * scoped to their City rather than a free-text field with no
     * relationship to anything. express_center itself is left in
     * place, unused, rather than dropped immediately - same caution
     * as every other column retired during this restructure.
     *
     * city_name is the fallback for a city typed that doesn't match
     * any row in `cities` yet — city_id stays the real relationship
     * when it DOES match; city_name only holds a value when it
     * doesn't, so a client's location is never blocked on the cities
     * table already having their exact city.
     *
     * logo_path follows the exact same pattern as Setting's own logo
     * (logo_path column + Storage::disk('public')) — an organization
     * account's branding, shown on its Overview tab.
     */
    public function up(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->foreignId('outlet_id')->nullable()->after('city_id')->constrained()->nullOnDelete();
            $table->string('city_name')->nullable()->after('outlet_id');
            $table->string('logo_path')->nullable()->after('company_name');
        });
    }

    public function down(): void
    {
        Schema::table('client_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outlet_id');
            $table->dropColumn(['city_name', 'logo_path']);
        });
    }
};

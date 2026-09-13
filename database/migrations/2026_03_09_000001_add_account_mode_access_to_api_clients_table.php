<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Same multi-account reasoning as everywhere else in this app: a
     * client's API access now belongs to one specific account, not
     * the client as a whole, since different accounts under the same
     * client can have entirely separate integrations. client_user_id
     * stays (external integration partners with no client account at
     * all still use it), but the old unique constraint on it alone
     * meant a client could only ever have ONE api_clients row, total —
     * that's replaced with a unique constraint on
     * (client_account_id, mode), since an account now gets up to two
     * rows: one Test key, one Live key.
     *
     * access_level is deliberately a single read_only/full_access
     * toggle, not per-action scopes — simpler to reason about and to
     * check in one place (CheckIpWhitelist), at the cost of coarser
     * control.
     */
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            // A plain index on client_user_id must exist before the
            // unique one can be dropped — MySQL won't drop an index
            // still backing its own foreign key constraint otherwise.
            $table->index('client_user_id', 'api_clients_client_user_id_plain_index');
            $table->dropUnique(['client_user_id']);
            $table->foreignId('client_account_id')->nullable()->after('client_user_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['test', 'live'])->default('live')->after('client_account_id');
            $table->enum('access_level', ['read_only', 'full_access'])->default('full_access')->after('mode');
            $table->unique(['client_account_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropUnique(['client_account_id', 'mode']);
            $table->dropConstrainedForeignId('client_account_id');
            $table->dropColumn(['mode', 'access_level']);
            $table->unique('client_user_id');
            $table->dropIndex('api_clients_client_user_id_plain_index');
        });
    }
};

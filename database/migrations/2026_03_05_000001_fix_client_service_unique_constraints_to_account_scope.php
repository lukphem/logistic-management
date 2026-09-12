<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * client_service_discounts and client_service_subscriptions both
     * still carried their original client_user_id + service_type_id
     * unique constraint from before the Client -> Account restructure
     * (2026_02_27_000003_add_client_account_id_to_related_tables.php's
     * own docblock flagged this as deliberately deferred to "a final
     * cleanup phase" that never actually landed for these two
     * constraints specifically).
     *
     * With a client able to have several accounts, that constraint is
     * now wrong: two DIFFERENT accounts belonging to the same client
     * subscribing to (or discounting) the same service type collide on
     * client_user_id + service_type_id even though the code has
     * already moved on to scoping everything by client_account_id
     * (ClientController::storeDiscount()/storeServiceSubscription()
     * both updateOrCreate() by client_account_id + service_type_id,
     * not client_user_id). Replaced with a constraint on
     * client_account_id + service_type_id, matching what the
     * application code actually queries by — one account, one row per
     * service type, independent of any other account the same client
     * might also have.
     *
     * Checked every other table client_account_id was added to in that
     * same restructure migration (departments, client_special_tariffs,
     * shipments) for the identical pattern — none of them carry a
     * client_user_id-scoped unique constraint, so this fix is isolated
     * to these two tables.
     */
    public function up(): void
    {
        Schema::table('client_service_discounts', function (Blueprint $table) {
            // The old unique index is what's currently satisfying
            // client_user_id's own foreign key requirement — MySQL
            // refuses to drop it otherwise (learned this dropping the
            // subscriptions one below first). A plain supporting index
            // keeps that FK valid once the unique constraint is gone.
            $table->index('client_user_id');
            $table->dropUnique(['client_user_id', 'service_type_id']);
            $table->unique(['client_account_id', 'service_type_id'], 'csd_client_account_service_unique');
        });

        Schema::table('client_service_subscriptions', function (Blueprint $table) {
            $table->index('client_user_id');
            $table->dropUnique('css_client_service_unique');
            $table->unique(['client_account_id', 'service_type_id'], 'css_client_account_service_unique');
        });
    }

    public function down(): void
    {
        Schema::table('client_service_subscriptions', function (Blueprint $table) {
            $table->dropUnique('css_client_account_service_unique');
            $table->dropIndex(['client_user_id']);
            $table->unique(['client_user_id', 'service_type_id'], 'css_client_service_unique');
        });

        Schema::table('client_service_discounts', function (Blueprint $table) {
            $table->dropUnique('csd_client_account_service_unique');
            $table->dropIndex(['client_user_id']);
            $table->unique(['client_user_id', 'service_type_id']);
        });
    }
};

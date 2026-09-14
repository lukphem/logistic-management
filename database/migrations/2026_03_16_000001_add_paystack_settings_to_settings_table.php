<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No separate test/live toggle — Paystack's own key prefixes
     * (pk_test_/sk_test_ vs pk_live_/sk_live_) already say which mode
     * a given key pair is for, so storing a redundant mode flag would
     * just be one more place the two could get out of sync. Whichever
     * pair is entered here is simply the pair that's used.
     *
     * Secret key stored encrypted (Laravel's encrypted cast) — this
     * is a live credential capable of initiating real charges against
     * the account, not something to sit in the database as plain
     * text the way most other settings do.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('paystack_enabled')->default(false)->after('waybill_design');
            $table->string('paystack_public_key')->nullable()->after('paystack_enabled');
            $table->text('paystack_secret_key')->nullable()->after('paystack_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['paystack_enabled', 'paystack_public_key', 'paystack_secret_key']);
        });
    }
};

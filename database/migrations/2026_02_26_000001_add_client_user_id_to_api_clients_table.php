<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * client_user_id links this api_clients row to a client's own
     * account (Security tab -> "Generate API access"), reusing the
     * exact same api_key/webhook_subscriptions/ip_whitelists machinery
     * already built for external integration partners rather than
     * duplicating it. Nullable + unique - an integration partner's
     * api_clients row has none; a client's has exactly one.
     *
     * api_response_format controls how binary/file fields (documents,
     * waybill images) are represented in this client's API responses -
     * a URL to fetch separately, or inlined as base64.
     */
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->foreignId('client_user_id')->nullable()->unique()->after('id')->constrained('users')->cascadeOnDelete();
            $table->enum('api_response_format', ['url', 'base64'])->default('url')->after('rate_limit_per_minute');
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
            $table->dropColumn('api_response_format');
        });
    }
};

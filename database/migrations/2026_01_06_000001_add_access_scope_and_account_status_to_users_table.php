<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * hub_id null = global access (sees/acts on every hub). Set = scoped
     * to that one hub only. A single nullable column keeps "global" as
     * the natural default with no separate flag that could drift out of
     * sync with it.
     *
     * account_status replaces the old binary is_active for anything
     * beyond a simple toggle — suspended/locked/terminated are distinct
     * for audit and security reasons even though all three currently
     * block login the same way. is_active is left in place (some
     * existing code still reads it) and kept in sync via the User model.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('hub_id')->nullable()->after('user_type')->constrained()->nullOnDelete();
            $table->enum('account_status', ['active', 'suspended', 'locked', 'terminated'])
                ->default('active')->after('is_active');
            $table->string('status_reason')->nullable()->after('account_status');
            $table->timestamp('status_changed_at')->nullable()->after('status_reason');
            $table->foreignId('status_changed_by')->nullable()->after('status_changed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hub_id');
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropColumn(['account_status', 'status_reason', 'status_changed_at']);
        });
    }
};

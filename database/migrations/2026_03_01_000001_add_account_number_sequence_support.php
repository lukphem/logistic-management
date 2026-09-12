<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client account numbers are moving to a location-based pattern:
     * {StateCode:2}{OutletCode:3}{StaffCode:3}{Sequence:5}, no
     * separators - e.g. LALOSJ0700001. State already has a 2-char
     * short_code. Outlet and staff users don't have a compact code
     * yet - outlets.short_code (3 chars) and users.staff_short_code
     * (3 chars, staff only) fill that gap. The existing
     * users.staff_id (STF-XXXXXX, random) stays as-is for whatever
     * already references it - staff_short_code is purely for this new
     * account-numbering scheme.
     *
     * account_number_sequences is the atomic counter backing the
     * Sequence portion - one row per (state, outlet, creating staff
     * member) combination, claimed with lockForUpdate() the same way
     * Setting::claimNextTrackingSequence() already claims tracking
     * numbers, so two simultaneous account creations for the same
     * combination can never collide on the same sequence number.
     * state_id/outlet_id are nullable - an account created without a
     * State/Outlet selected (both optional on the account form) still
     * gets a counter row, scoped by NULL + the creating staff member,
     * rather than blocking account creation on location being filled
     * in first.
     */
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('short_code', 3)->nullable()->unique()->after('code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_short_code', 3)->nullable()->unique()->after('staff_id');
        });

        Schema::create('account_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('staff_user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('next_sequence')->default(1);
            $table->timestamps();

            $table->unique(['state_id', 'outlet_id', 'staff_user_id'], 'ans_state_outlet_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_number_sequences');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('staff_short_code');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn('short_code');
        });
    }
};

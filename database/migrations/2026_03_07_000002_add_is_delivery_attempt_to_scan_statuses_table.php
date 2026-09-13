<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scan statuses are fully staff-configurable (no fixed set of
     * keys), so there's no reliable built-in way to know which of a
     * given company's statuses represents "a delivery attempt was
     * made" — "Attempted, No One Home" and "Attempted, Address Not
     * Found" might both count, while "Out For Delivery" itself
     * shouldn't. Same pattern as the existing is_terminal flag: staff
     * mark which of their own statuses qualify, rather than the app
     * guessing from the label text.
     */
    public function up(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->boolean('is_delivery_attempt')->default(false)->after('is_terminal');
        });
    }

    public function down(): void
    {
        Schema::table('scan_statuses', function (Blueprint $table) {
            $table->dropColumn('is_delivery_attempt');
        });
    }
};

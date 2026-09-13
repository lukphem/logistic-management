<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Real crash, confirmed from a production error report: a genuine
     * address (landmarks, directions — normal for Nigerian addresses)
     * exceeded origin_address/destination_address's VARCHAR(255) and
     * was rejected by MySQL with no warning anywhere upstream, since
     * the validation layer had no max: rule at all for either field —
     * "required|string" happily accepted a value the database itself
     * couldn't store. package_description widened for the same
     * practical reason (a real description of a bulk/complex package
     * can run long) even though it hadn't crashed yet — same unbounded
     * risk, same fix, before it does.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->text('origin_address')->change();
            $table->text('destination_address')->change();
            $table->text('package_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('origin_address')->change();
            $table->string('destination_address')->change();
            $table->string('package_description')->nullable()->change();
        });
    }
};

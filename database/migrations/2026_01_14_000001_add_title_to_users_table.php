<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `title` is new (Mr/Mrs/Miss/etc., for formal address — waybills,
     * letters). `gender` already existed as free text (Increment 16); its
     * column type doesn't need to change since it's already a plain
     * string — only the form now constrains it to a fixed list rather
     * than letting anyone type anything.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('title')->nullable()->after('last_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};

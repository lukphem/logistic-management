<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Receiver-only, not sender — a second contact number for the
     * person receiving the package (often needed when the primary
     * number is unreachable at delivery time) isn't a symmetric need
     * on the sending side, so this isn't added to both.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('receiver_alternate_phone', 20)->nullable()->after('receiver_phone');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('receiver_alternate_phone');
        });
    }
};

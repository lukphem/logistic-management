<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->boolean('is_cod')->default(false)->after('total_amount');
            $table->decimal('cod_amount', 12, 2)->default(0)->after('is_cod');
            $table->timestamp('cod_remitted_at')->nullable()->after('cod_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['is_cod', 'cod_amount', 'cod_remitted_at']);
        });
    }
};

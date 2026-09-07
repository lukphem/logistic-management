<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Explicit short names for the foreign key and unique index below —
     * Laravel's auto-generated names (table_column_foreign / _unique)
     * exceed MySQL's 64-character identifier limit for this table, since
     * both the table name and client_special_tariff_id are long on
     * their own. Caught only by actually running this migration through
     * Laravel; a hand-written raw-SQL translation for testing doesn't
     * reproduce it, since MySQL auto-generates its own short constraint
     * name when none is given explicitly - only Laravel's own naming
     * convention hits the limit.
     */
    public function up(): void
    {
        Schema::create('client_special_tariff_zone_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_special_tariff_id');
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->decimal('charge', 12, 2);
            $table->decimal('additional_charge', 12, 2)->default(0);
            $table->unsignedInteger('transit_days')->nullable();
            $table->timestamps();

            $table->foreign('client_special_tariff_id', 'cstzp_tariff_id_foreign')
                ->references('id')->on('client_special_tariffs')->cascadeOnDelete();
            $table->unique(['client_special_tariff_id', 'zone_id'], 'cstzp_tariff_zone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_special_tariff_zone_prices');
    }
};

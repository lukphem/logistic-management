<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Correction: a service like "Packaging" isn't one flat price — it
     * has different types (Small Box, Medium Box, Large Box, Envelope),
     * each priced differently. additional_services becomes the
     * category/name only; the price moves to a new child table,
     * additional_service_options, one row per variant. A service with
     * only one real variant just has one option — this doesn't force
     * every service to have multiple types, it just supports it when
     * needed.
     */
    public function up(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::create('additional_service_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('additional_service_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Small Box", "Medium Box"
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_service_options');

        Schema::table('additional_services', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('name');
        });
    }
};

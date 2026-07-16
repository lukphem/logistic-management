<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Onforwarding" — a location outside a hub's direct network that
     * needs handing off to a third party / local courier to complete the
     * final leg, which typically carries an extra charge. This is a
     * billing concept, not a location-hierarchy one — deliberately kept
     * as its own small lookup table (not a boolean on City/District) so
     * more than one tier can exist (e.g. "Onforwarding - Near" vs
     * "Onforwarding - Remote" at different fee levels), and so the fee
     * amount lives in one place instead of being duplicated per city.
     */
    public function up(): void
    {
        Schema::create('onforwarding_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Standard", "Onforwarding - Near", "Onforwarding - Remote"
            $table->decimal('surcharge_amount', 12, 2)->default(0);
            $table->boolean('is_default')->default(false); // the "no extra charge" baseline classification
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onforwarding_classifications');
    }
};

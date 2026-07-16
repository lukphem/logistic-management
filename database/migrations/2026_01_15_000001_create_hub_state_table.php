<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A hub's single `city_id` (Increment 17) is its home location — where
     * it physically sits. This is different: the set of states it
     * actually picks up from and delivers to, which is often broader than
     * just its home city's state (a hub can easily cover several
     * neighboring states). Many-to-many since a state can also be covered
     * by more than one hub.
     */
    public function up(): void
    {
        Schema::create('hub_state', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained()->cascadeOnDelete();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hub_id', 'state_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_state');
    }
};

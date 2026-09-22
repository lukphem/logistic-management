<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Previously an upload's validated rows lived only in a
     * temporary token file, replaced whole on every re-upload — so
     * fixing one bad row meant re-uploading everything, and there
     * was no way to review what an earlier upload had actually
     * produced. Now each row a batch's uploads produce (valid or
     * invalid) is its own record: uploads accumulate onto the same
     * batch instead of replacing each other, a single bad row can be
     * deleted on its own, and store() consumes real rows here
     * instead of a token that only existed until the next request.
     */
    public function up(): void
    {
        Schema::create('bulk_shipment_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_shipment_batch_id')->constrained('bulk_shipment_batches')->cascadeOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->enum('status', ['valid', 'invalid']);
            $table->string('receiver_name')->nullable();
            $table->json('row_data')->nullable();
            $table->json('display_data')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_shipment_batch_rows');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Step 1 of bulk upload (shipper + service type details) now
     * creates a real record with its own batch number, rather than
     * everything living in one combined submission — so if the CSV
     * has errors, the person fixes the file and re-uploads to the
     * SAME batch instead of re-entering the shipper/service details
     * from scratch. client_account_id null means walk-in: an outlet
     * running its own bulk batch for a cash customer, not billed to
     * any registered account.
     */
    public function up(): void
    {
        Schema::create('bulk_shipment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->foreignId('client_account_id')->nullable()->constrained('client_accounts')->nullOnDelete();
            $table->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('billing_model');
            $table->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->string('sender_address');
            $table->string('sender_email')->nullable();
            $table->foreignId('origin_hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('origin_outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_shipment_batches');
    }
};

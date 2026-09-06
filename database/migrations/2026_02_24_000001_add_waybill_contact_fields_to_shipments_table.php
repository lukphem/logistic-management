<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standard waybill information that was missing entirely — every
     * courier waybill (DHL/FedEx/UPS included) needs who's sending it,
     * who's receiving it, and what's inside, at minimum. Sender/receiver
     * name+phone and package_description are the DHL-standard compulsory
     * fields; email on both sides and special_instructions are optional
     * (DHL-standard, just not compulsory there either). None of this
     * existed before - origin_address/destination_address alone covered
     * *where*, never *who* or *what*.
     */
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('sender_name')->nullable()->after('client_user_id');
            $table->string('sender_phone')->nullable()->after('sender_name');
            $table->string('sender_email')->nullable()->after('sender_phone');
            $table->string('receiver_name')->nullable()->after('sender_email');
            $table->string('receiver_phone')->nullable()->after('receiver_name');
            $table->string('receiver_email')->nullable()->after('receiver_phone');
            $table->string('package_description')->nullable()->after('receiver_email');
            $table->text('special_instructions')->nullable()->after('package_description');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name', 'sender_phone', 'sender_email',
                'receiver_name', 'receiver_phone', 'receiver_email',
                'package_description', 'special_instructions',
            ]);
        });
    }
};

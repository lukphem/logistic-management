<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * staff_id, first_name, last_name, phone_number, photo_path are the
     * requested additions. The rest (dob, gender, address, job_title,
     * date_joined, employment_type, emergency contact) are optional staff
     * details that commonly matter for an HR/ops record but aren't
     * required to create an account — all nullable.
     *
     * `name` is left in place rather than dropped: dozens of existing
     * views/relations read $user->name (assignedRider->name,
     * handler->name, etc.). first_name/last_name are now the source of
     * truth and `name` is kept in sync automatically — see
     * User::booted()'s saving hook — so nothing else in the app needed
     * to change.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_id')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('staff_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone_number')->nullable()->after('email');
            $table->string('photo_path')->nullable()->after('phone_number');

            // Optional HR/profile details — not compulsory.
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->text('address')->nullable();
            $table->string('job_title')->nullable();
            $table->date('date_joined')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'staff_id', 'first_name', 'last_name', 'phone_number', 'photo_path',
                'date_of_birth', 'gender', 'address', 'job_title', 'date_joined',
                'employment_type', 'emergency_contact_name', 'emergency_contact_phone',
            ]);
        });
    }
};

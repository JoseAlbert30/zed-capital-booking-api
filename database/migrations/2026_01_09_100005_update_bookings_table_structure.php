<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Rename booking_date to booked_date
            $table->renameColumn('booking_date', 'booked_date');
            
            // Rename time_slot to booked_time
            $table->renameColumn('time_slot', 'booked_time');
            
            // Remove fields not in new schema
            if (Schema::hasColumn('bookings', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('bookings', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('bookings', 'location')) {
                $table->dropColumn('location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('booked_date', 'booking_date');
            $table->renameColumn('booked_time', 'time_slot');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
        });
    }
};

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
            $table->string('handover_pdf')->nullable();
            $table->string('handover_photo')->nullable();
            $table->timestamp('handover_completed_at')->nullable();
            $table->foreignId('handover_completed_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['handover_completed_by']);
            $table->dropColumn(['handover_pdf', 'handover_photo', 'handover_completed_at', 'handover_completed_by']);
        });
    }
};

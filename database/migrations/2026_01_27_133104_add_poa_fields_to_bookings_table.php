<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_owner_attending')->default(true)->after('status');
            $table->string('poa_document')->nullable()->after('is_owner_attending');
            $table->string('attorney_id_document')->nullable()->after('poa_document');
        });
        
        // Update existing enum values for status
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending_poa_approval', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'confirmed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['is_owner_attending', 'poa_document', 'attorney_id_document']);
        });
        
        // Revert status enum
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'confirmed'");
    }
};

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
            $table->renameColumn('handover_pdf', 'handover_checklist');
            $table->string('handover_declaration')->nullable()->after('handover_checklist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('handover_declaration');
            $table->renameColumn('handover_checklist', 'handover_pdf');
        });
    }
};

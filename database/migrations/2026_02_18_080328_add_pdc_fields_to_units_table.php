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
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('pdc_in_hand', 15, 2)->nullable()->after('due_after_completion');
            $table->integer('pdc_count')->nullable()->after('pdc_in_hand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['pdc_in_hand', 'pdc_count']);
        });
    }
};

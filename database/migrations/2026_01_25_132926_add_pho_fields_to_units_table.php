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
            $table->decimal('upon_completion_amount', 15, 2)->nullable()->after('total_amount_paid');
            $table->decimal('due_after_completion', 15, 2)->nullable()->after('upon_completion_amount');
            $table->boolean('has_pho')->default(false)->after('due_after_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['upon_completion_amount', 'due_after_completion', 'has_pho']);
        });
    }
};

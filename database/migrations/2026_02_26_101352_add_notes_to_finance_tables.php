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
        // Add notes to finance_pops table
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('created_by');
        });

        // Add notes to finance_s_o_a_s table
        Schema::table('finance_s_o_a_s', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('created_by');
        });

        // Add notes to finance_penalties table
        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('created_by');
        });

        // Add notes to finance_n_o_c_s table
        Schema::table('finance_n_o_c_s', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('finance_s_o_a_s', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        Schema::table('finance_n_o_c_s', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};

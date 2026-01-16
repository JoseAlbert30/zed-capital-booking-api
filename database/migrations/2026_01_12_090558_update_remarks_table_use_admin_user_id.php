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
        Schema::table('remarks', function (Blueprint $table) {
            // Drop the admin_name column
            $table->dropColumn('admin_name');
            // Add admin_user_id as foreign key
            $table->unsignedBigInteger('admin_user_id')->nullable()->after('type');
            $table->foreign('admin_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remarks', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['admin_user_id']);
            $table->dropColumn('admin_user_id');
            // Restore admin_name column
            $table->string('admin_name')->nullable();
        });
    }
};

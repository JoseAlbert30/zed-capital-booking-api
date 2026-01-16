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
        Schema::table('users', function (Blueprint $table) {
            // Rename name to full_name
            $table->renameColumn('name', 'full_name');
            
            // Add mobile_number if it doesn't exist (rename from phone)
            if (Schema::hasColumn('users', 'phone')) {
                $table->renameColumn('phone', 'mobile_number');
            } else {
                $table->string('mobile_number')->nullable()->after('email');
            }
            
            // Remove address column as it's not in the new schema
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('full_name', 'name');
            $table->renameColumn('mobile_number', 'phone');
            $table->string('address')->nullable();
        });
    }
};

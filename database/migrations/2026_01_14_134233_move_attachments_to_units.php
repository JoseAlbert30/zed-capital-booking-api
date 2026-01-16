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
        Schema::table('user_attachments', function (Blueprint $table) {
            // Make user_id nullable since attachments will now primarily belong to units
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // unit_id already exists, just make sure it's set up properly
            // We'll keep both user_id and unit_id for flexibility
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_attachments', function (Blueprint $table) {
            // Revert user_id to not nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};

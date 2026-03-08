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
        Schema::create('finance_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dev_user_id')->constrained()->onDelete('cascade');
            $table->string('project_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique dev_user-project combination
            $table->unique(['dev_user_id', 'project_name']);

            // Index for faster lookups
            $table->index(['dev_user_id', 'is_active']);
            $table->index('project_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_access');
    }
};

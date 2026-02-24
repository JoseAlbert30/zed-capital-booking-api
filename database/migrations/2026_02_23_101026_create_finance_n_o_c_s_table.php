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
        Schema::create('finance_n_o_c_s', function (Blueprint $table) {
            $table->id();
            $table->string('noc_number')->unique();
            $table->string('project_name');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->string('unit_number');
            $table->string('noc_name');
            $table->text('description')->nullable();
            
            // Document tracking
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->timestamp('document_uploaded_at')->nullable();
            $table->string('document_uploaded_by')->nullable();
            
            // Notification tracking
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            
            // Creator tracking
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_n_o_c_s');
    }
};

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
        Schema::create('finance_pops', function (Blueprint $table) {
            $table->id();
            $table->string('pop_number')->unique();
            $table->string('project_name');
            $table->string('unit_number');
            $table->decimal('amount', 15, 2);
            $table->string('attachment_path');
            $table->string('attachment_name');
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['project_name', 'created_at']);
            $table->index('pop_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_pops');
    }
};

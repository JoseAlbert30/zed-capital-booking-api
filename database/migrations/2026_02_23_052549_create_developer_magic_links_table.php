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
        Schema::create('developer_magic_links', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('developer_email');
            $table->string('developer_name')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('first_used_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->integer('access_count')->default(0);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['token', 'is_active']);
            $table->index('project_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_magic_links');
    }
};

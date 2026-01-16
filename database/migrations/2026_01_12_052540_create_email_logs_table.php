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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('recipient_email');
            $table->string('recipient_name');
            $table->string('subject');
            $table->text('message');
            $table->string('email_type')->default('soa'); // soa, notification, etc.
            $table->string('status')->default('sent'); // sent, failed, queued
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // units, projects, attachments info
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

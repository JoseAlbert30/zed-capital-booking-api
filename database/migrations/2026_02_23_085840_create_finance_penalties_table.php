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
        Schema::create('finance_penalties', function (Blueprint $table) {
            $table->id();
            $table->string('penalty_number')->unique();
            $table->string('project_name');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_number');
            $table->string('penalty_name');
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->timestamp('document_uploaded_at')->nullable();
            $table->string('document_uploaded_by')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_penalties');
    }
};

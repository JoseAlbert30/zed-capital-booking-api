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
        Schema::create('finance_s_o_a_s', function (Blueprint $table) {
            $table->id();
            $table->string('soa_number')->unique();
            $table->string('project_name');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_number');
            $table->text('description')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->timestamp('document_uploaded_at')->nullable();
            $table->string('document_uploaded_by')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->boolean('viewed_by_developer')->default(false);
            $table->timestamp('viewed_at')->nullable();
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
        Schema::dropIfExists('finance_s_o_a_s');
    }
};

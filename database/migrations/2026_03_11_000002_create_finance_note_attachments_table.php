<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('note_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('note_id')->references('id')->on('finance_notes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_note_attachments');
    }
};

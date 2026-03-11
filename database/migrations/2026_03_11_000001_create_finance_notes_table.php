<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_notes', function (Blueprint $table) {
            $table->id();
            $table->string('noteable_type');    // 'noc', 'pop', 'soa', 'penalty', 'thirdparty'
            $table->unsignedBigInteger('noteable_id');
            $table->string('project_name');
            $table->text('message');
            $table->enum('sent_by_type', ['admin', 'developer']);
            $table->unsignedBigInteger('sent_by_id')->nullable();
            $table->string('sent_by_name');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['noteable_type', 'noteable_id']);
            $table->index('project_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_notes');
    }
};

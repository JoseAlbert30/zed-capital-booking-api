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
        Schema::create('soa_generation_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->integer('total_soas')->default(0);
            $table->integer('generated_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('unit_ids')->nullable();
            $table->string('initiated_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('batch_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soa_generation_batches');
    }
};

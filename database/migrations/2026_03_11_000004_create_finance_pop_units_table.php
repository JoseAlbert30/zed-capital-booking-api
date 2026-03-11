<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_pop_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pop_id')->constrained('finance_pops')->onDelete('cascade');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('unit_number');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->timestamp('receipt_uploaded_at')->nullable();
            $table->string('receipt_uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['pop_id']);
            $table->index(['pop_id', 'unit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_pop_units');
    }
};

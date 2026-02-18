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
        Schema::create('sales_offers', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('unit_no');
            $table->integer('bedrooms');
            $table->decimal('sqft', 10, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('discounted_price', 15, 2);
            $table->text('payment_plan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_offers');
    }
};

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
        Schema::table('sales_offers', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['selling_price', 'discounted_price', 'payment_plan', 'dld_fee']);
            
            // Add new columns
            $table->decimal('price_5050', 15, 2)->after('sqft');
            $table->decimal('dld_5050', 15, 2)->after('price_5050');
            $table->decimal('price_3070', 15, 2)->nullable()->after('dld_5050');
            $table->decimal('dld_3070', 15, 2)->nullable()->after('price_3070');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_offers', function (Blueprint $table) {
            // Restore old columns
            $table->decimal('selling_price', 15, 2)->after('sqft');
            $table->decimal('discounted_price', 15, 2)->after('selling_price');
            $table->string('payment_plan')->after('discounted_price');
            $table->decimal('dld_fee', 15, 2)->after('discounted_price');
            
            // Drop new columns
            $table->dropColumn(['price_5050', 'dld_5050', 'price_3070', 'dld_3070']);
        });
    }
};

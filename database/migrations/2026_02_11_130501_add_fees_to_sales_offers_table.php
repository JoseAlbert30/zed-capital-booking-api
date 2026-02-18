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
            $table->decimal('dld_fee', 15, 2)->nullable()->after('discounted_price');
            $table->decimal('admin_fee', 15, 2)->nullable()->after('dld_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_offers', function (Blueprint $table) {
            $table->dropColumn(['dld_fee', 'admin_fee']);
        });
    }
};

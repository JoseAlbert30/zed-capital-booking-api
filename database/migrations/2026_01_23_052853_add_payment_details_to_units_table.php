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
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('total_unit_price', 15, 2)->nullable()->after('square_footage');
            $table->decimal('dld_fees', 15, 2)->nullable()->after('total_unit_price');
            $table->decimal('admin_fee', 15, 2)->nullable()->after('dld_fees');
            $table->decimal('amount_to_pay', 15, 2)->nullable()->after('admin_fee');
            $table->decimal('total_amount_paid', 15, 2)->nullable()->after('amount_to_pay');
            $table->decimal('outstanding_amount', 15, 2)->nullable()->after('total_amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'total_unit_price',
                'dld_fees',
                'admin_fee',
                'amount_to_pay',
                'total_amount_paid',
                'outstanding_amount'
            ]);
        });
    }
};

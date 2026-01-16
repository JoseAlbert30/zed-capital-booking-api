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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'partial', 'fully_paid'])->default('pending')->after('password');
            $table->dateTime('payment_date')->nullable()->after('payment_status');
            $table->string('phone')->nullable()->after('payment_date');
            $table->text('address')->nullable()->after('phone');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'payment_date', 'phone', 'address']);
        });
    }
};

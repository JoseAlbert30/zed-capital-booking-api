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
        Schema::table('finance_penalties', function (Blueprint $table) {
            // Remove old sent_to_buyer fields (replaced by receipt_sent_to_buyer)
            if (Schema::hasColumn('finance_penalties', 'sent_to_buyer')) {
                $table->dropColumn('sent_to_buyer');
            }
            if (Schema::hasColumn('finance_penalties', 'sent_to_buyer_at')) {
                $table->dropColumn('sent_to_buyer_at');
            }
            if (Schema::hasColumn('finance_penalties', 'sent_to_buyer_email')) {
                $table->dropColumn('sent_to_buyer_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->boolean('sent_to_buyer')->default(false);
            $table->timestamp('sent_to_buyer_at')->nullable();
            $table->string('sent_to_buyer_email')->nullable();
        });
    }
};

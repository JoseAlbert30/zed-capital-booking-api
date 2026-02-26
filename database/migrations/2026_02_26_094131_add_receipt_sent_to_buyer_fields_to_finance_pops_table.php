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
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->boolean('receipt_sent_to_buyer')->default(false)->after('receipt_uploaded_by');
            $table->timestamp('receipt_sent_to_buyer_at')->nullable()->after('receipt_sent_to_buyer');
            $table->string('receipt_sent_to_buyer_email')->nullable()->after('receipt_sent_to_buyer_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn(['receipt_sent_to_buyer', 'receipt_sent_to_buyer_at', 'receipt_sent_to_buyer_email']);
        });
    }
};

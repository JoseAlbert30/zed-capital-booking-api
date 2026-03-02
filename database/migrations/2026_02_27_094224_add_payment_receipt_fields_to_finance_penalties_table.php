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
            // Proof of Payment fields (uploaded by admin after developer creates penalty)
            $table->string('proof_of_payment_path')->nullable()->after('document_name');
            $table->string('proof_of_payment_name')->nullable()->after('proof_of_payment_path');
            $table->timestamp('proof_of_payment_uploaded_at')->nullable()->after('proof_of_payment_name');
            
            // Receipt fields (uploaded by developer)
            $table->string('receipt_path')->nullable()->after('proof_of_payment_uploaded_at');
            $table->string('receipt_name')->nullable()->after('receipt_path');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_name');
            
            // Send receipt to buyer tracking
            $table->boolean('receipt_sent_to_buyer')->default(false)->after('receipt_uploaded_at');
            $table->timestamp('receipt_sent_to_buyer_at')->nullable()->after('receipt_sent_to_buyer');
            $table->string('receipt_sent_to_buyer_email')->nullable()->after('receipt_sent_to_buyer_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->dropColumn([
                'proof_of_payment_path',
                'proof_of_payment_name',
                'proof_of_payment_uploaded_at',
                'receipt_path',
                'receipt_name',
                'receipt_uploaded_at',
                'receipt_sent_to_buyer',
                'receipt_sent_to_buyer_at',
                'receipt_sent_to_buyer_email',
            ]);
        });
    }
};

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
        Schema::table('finance_thirdparties', function (Blueprint $table) {
            // Add proof of payment fields
            $table->string('proof_of_payment_path')->nullable()->after('signed_document_uploaded_at');
            $table->string('proof_of_payment_name')->nullable()->after('proof_of_payment_path');
            $table->timestamp('proof_of_payment_uploaded_at')->nullable()->after('proof_of_payment_name');
            
            // Add receipt sent to buyer tracking
            $table->boolean('receipt_sent_to_buyer')->default(false)->after('receipt_uploaded_by');
            $table->timestamp('receipt_sent_to_buyer_at')->nullable()->after('receipt_sent_to_buyer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_thirdparties', function (Blueprint $table) {
            $table->dropColumn([
                'proof_of_payment_path',
                'proof_of_payment_name',
                'proof_of_payment_uploaded_at',
                'receipt_sent_to_buyer',
                'receipt_sent_to_buyer_at',
            ]);
        });
    }
};

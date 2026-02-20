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
            // Receipt upload by developer
            $table->string('receipt_path')->nullable()->after('attachment_name');
            $table->string('receipt_name')->nullable()->after('receipt_path');
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_name');
            
            // SOA Request
            $table->boolean('soa_requested')->default(false)->after('receipt_uploaded_at');
            $table->timestamp('soa_requested_at')->nullable()->after('soa_requested');
            
            // SOA Docs upload by developer
            $table->string('soa_docs_path')->nullable()->after('soa_requested_at');
            $table->string('soa_docs_name')->nullable()->after('soa_docs_path');
            $table->timestamp('soa_docs_uploaded_at')->nullable()->after('soa_docs_name');
            
            // SOA sent to buyer
            $table->boolean('soa_sent_to_buyer')->default(false)->after('soa_docs_uploaded_at');
            $table->timestamp('soa_sent_to_buyer_at')->nullable()->after('soa_sent_to_buyer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_path',
                'receipt_name',
                'receipt_uploaded_at',
                'soa_requested',
                'soa_requested_at',
                'soa_docs_path',
                'soa_docs_name',
                'soa_docs_uploaded_at',
                'soa_sent_to_buyer',
                'soa_sent_to_buyer_at',
            ]);
        });
    }
};

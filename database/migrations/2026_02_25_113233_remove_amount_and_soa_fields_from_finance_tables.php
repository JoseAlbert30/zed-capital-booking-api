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
        // Remove amount and SOA-related fields from finance_pops table
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn([
                'amount',
                'soa_requested',
                'soa_requested_at',
                'soa_docs_path',
                'soa_docs_name',
                'soa_docs_uploaded_at',
                'soa_uploaded_by',
                'soa_sent_to_buyer',
                'soa_sent_to_buyer_at',
            ]);
        });

        // Add buyer_email field to finance_pops table
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->string('buyer_email')->nullable()->after('unit_number');
        });

        // Remove amount field from finance_penalties table
        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore amount and SOA-related fields to finance_pops table
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->after('unit_number');
            $table->boolean('soa_requested')->default(false);
            $table->timestamp('soa_requested_at')->nullable();
            $table->string('soa_docs_path')->nullable();
            $table->string('soa_docs_name')->nullable();
            $table->timestamp('soa_docs_uploaded_at')->nullable();
            $table->unsignedBigInteger('soa_uploaded_by')->nullable();
            $table->boolean('soa_sent_to_buyer')->default(false);
            $table->timestamp('soa_sent_to_buyer_at')->nullable();
        });

        // Remove buyer_email field from finance_pops table
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn('buyer_email');
        });

        // Restore amount field to finance_penalties table
        Schema::table('finance_penalties', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->after('penalty_name');
        });
    }
};

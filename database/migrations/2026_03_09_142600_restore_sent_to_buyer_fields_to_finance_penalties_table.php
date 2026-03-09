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
            // Re-add sent_to_buyer fields (for penalty invoice sent to buyer)
            // These are separate from receipt_sent_to_buyer (for receipt sent to buyer)
            if (!Schema::hasColumn('finance_penalties', 'sent_to_buyer')) {
                $table->boolean('sent_to_buyer')->default(false)->after('receipt_sent_to_buyer_email');
            }
            if (!Schema::hasColumn('finance_penalties', 'sent_to_buyer_at')) {
                $table->timestamp('sent_to_buyer_at')->nullable()->after('sent_to_buyer');
            }
            if (!Schema::hasColumn('finance_penalties', 'sent_to_buyer_email')) {
                $table->string('sent_to_buyer_email')->nullable()->after('sent_to_buyer_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_penalties', function (Blueprint $table) {
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
};

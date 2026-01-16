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
            $table->enum('payment_status', ['pending', 'partial', 'fully_paid'])->default('pending')->after('status');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->boolean('has_mortgage')->default(false)->after('payment_date');
            $table->boolean('handover_ready')->default(false)->after('has_mortgage');
            $table->boolean('handover_email_sent')->default(false)->after('handover_ready');
            $table->timestamp('handover_email_sent_at')->nullable()->after('handover_email_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_date', 'has_mortgage', 'handover_ready', 'handover_email_sent', 'handover_email_sent_at']);
        });
    }
};

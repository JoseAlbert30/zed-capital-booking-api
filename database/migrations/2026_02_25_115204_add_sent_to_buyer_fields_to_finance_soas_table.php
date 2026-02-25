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
        Schema::table('finance_s_o_a_s', function (Blueprint $table) {
            $table->boolean('sent_to_buyer')->default(false)->after('viewed_at');
            $table->timestamp('sent_to_buyer_at')->nullable()->after('sent_to_buyer');
            $table->string('sent_to_buyer_email')->nullable()->after('sent_to_buyer_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_s_o_a_s', function (Blueprint $table) {
            $table->dropColumn(['sent_to_buyer', 'sent_to_buyer_at', 'sent_to_buyer_email']);
        });
    }
};

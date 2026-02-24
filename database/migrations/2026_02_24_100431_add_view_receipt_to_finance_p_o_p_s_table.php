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
            $table->boolean('viewed_by_developer')->default(false)->after('notification_sent_at');
            $table->timestamp('viewed_at')->nullable()->after('viewed_by_developer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn(['viewed_by_developer', 'viewed_at']);
        });
    }
};

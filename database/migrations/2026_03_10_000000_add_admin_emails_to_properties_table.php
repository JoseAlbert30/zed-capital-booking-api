<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('admin_emails')->nullable()->after('cc_emails');
            $table->string('admin_cc_emails')->nullable()->after('admin_emails');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['admin_emails', 'admin_cc_emails']);
        });
    }
};

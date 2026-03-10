<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->text('admin_emails')->nullable()->change();
            $table->text('admin_cc_emails')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('admin_emails')->nullable()->change();
            $table->string('admin_cc_emails')->nullable()->change();
        });
    }
};

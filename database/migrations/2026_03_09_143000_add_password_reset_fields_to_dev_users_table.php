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
        Schema::table('dev_users', function (Blueprint $table) {
            if (!Schema::hasColumn('dev_users', 'must_reset_password')) {
                $table->boolean('must_reset_password')->default(false)->after('password');
            }
            if (!Schema::hasColumn('dev_users', 'last_login')) {
                $table->timestamp('last_login')->nullable()->after('must_reset_password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dev_users', function (Blueprint $table) {
            if (Schema::hasColumn('dev_users', 'must_reset_password')) {
                $table->dropColumn('must_reset_password');
            }
            if (Schema::hasColumn('dev_users', 'last_login')) {
                $table->dropColumn('last_login');
            }
        });
    }
};

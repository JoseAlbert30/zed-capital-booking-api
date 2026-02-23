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
        Schema::table('developer_magic_links', function (Blueprint $table) {
            $table->string('password')->nullable()->after('developer_name');
            $table->boolean('password_set')->default(false)->after('password');
            $table->timestamp('password_set_at')->nullable()->after('password_set');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('developer_magic_links', function (Blueprint $table) {
            $table->dropColumn(['password', 'password_set', 'password_set_at']);
        });
    }
};

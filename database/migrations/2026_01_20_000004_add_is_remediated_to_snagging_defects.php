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
        Schema::table('snagging_defects', function (Blueprint $table) {
            $table->boolean('is_remediated')->default(false)->after('agreed_remediation_action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('snagging_defects', function (Blueprint $table) {
            $table->dropColumn('is_remediated');
        });
    }
};

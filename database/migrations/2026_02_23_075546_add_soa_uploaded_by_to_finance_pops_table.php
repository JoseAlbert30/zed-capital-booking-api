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
            $table->string('soa_uploaded_by')->nullable()->after('soa_docs_uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_pops', function (Blueprint $table) {
            $table->dropColumn('soa_uploaded_by');
        });
    }
};

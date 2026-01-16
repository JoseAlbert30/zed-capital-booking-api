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
            $table->string('floor')->nullable()->after('unit');
            $table->string('building')->nullable()->after('floor');
            $table->decimal('square_footage', 10, 2)->nullable()->after('building');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['floor', 'building', 'square_footage']);
        });
    }
};

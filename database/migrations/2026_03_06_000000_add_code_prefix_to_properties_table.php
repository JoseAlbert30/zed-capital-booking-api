<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('code_prefix', 10)->nullable()->after('project_name');
        });

        // Auto-generate code_prefix for existing properties
        $properties = DB::table('properties')->get();
        foreach ($properties as $property) {
            // Generate prefix from project name (first 4 alphanumeric chars, uppercase)
            $rawPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $property->project_name));
            if (strlen($rawPrefix) < 4) {
                $rawPrefix = str_pad($rawPrefix, 4, 'X');
            }
            $prefix = substr($rawPrefix, 0, 4);

            DB::table('properties')
                ->where('id', $property->id)
                ->update(['code_prefix' => $prefix]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('code_prefix');
        });
    }
};

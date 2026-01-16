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
        // Update existing attachments to generate proper file_path from filename
        DB::table('user_attachments')
            ->whereNotNull('unit_id')
            ->whereNull('file_path')
            ->update([
                'file_path' => DB::raw("CONCAT('attachments/', filename)")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};

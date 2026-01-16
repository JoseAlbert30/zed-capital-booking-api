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
        // First, convert existing text data to JSON format
        $users = DB::table('users')->whereNotNull('remarks')->get();
        
        foreach ($users as $user) {
            if ($user->remarks) {
                // Convert existing text to timeline format
                $timeline = [
                    [
                        'date' => now()->format('Y-m-d'),
                        'time' => now()->format('H:i:s'),
                        'event' => $user->remarks
                    ]
                ];
                
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['remarks' => json_encode($timeline)]);
            }
        }
        
        // Now change the column type to JSON
        Schema::table('users', function (Blueprint $table) {
            $table->json('remarks')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('remarks')->nullable()->change();
        });
    }
};

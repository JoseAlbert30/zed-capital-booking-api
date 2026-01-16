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
        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('time');
            $table->text('event');
            $table->string('type', 50)->default('system'); // email_sent, payment_update, manual_note, etc.
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
        });

        // Migrate existing remarks from users table
        $users = DB::table('users')
            ->whereNotNull('remarks')
            ->select('id', 'remarks')
            ->get();

        foreach ($users as $user) {
            if ($user->remarks) {
                $remarksArray = json_decode($user->remarks, true);
                
                if (is_array($remarksArray)) {
                    foreach ($remarksArray as $remark) {
                        DB::table('remarks')->insert([
                            'user_id' => $user->id,
                            'date' => $remark['date'] ?? now()->format('Y-m-d'),
                            'time' => $remark['time'] ?? now()->format('H:i:s'),
                            'event' => $remark['event'] ?? 'Migrated remark',
                            'type' => $remark['type'] ?? 'system',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Drop remarks column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add remarks column back to users table
        Schema::table('users', function (Blueprint $table) {
            $table->json('remarks')->nullable();
        });

        // Migrate data back to users table
        $remarks = DB::table('remarks')
            ->orderBy('user_id')
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $userRemarks = [];
        foreach ($remarks as $remark) {
            if (!isset($userRemarks[$remark->user_id])) {
                $userRemarks[$remark->user_id] = [];
            }
            $userRemarks[$remark->user_id][] = [
                'date' => $remark->date,
                'time' => $remark->time,
                'event' => $remark->event,
                'type' => $remark->type,
            ];
        }

        foreach ($userRemarks as $userId => $remarks) {
            DB::table('users')
                ->where('id', $userId)
                ->update(['remarks' => json_encode($remarks)]);
        }

        Schema::dropIfExists('remarks');
    }
};

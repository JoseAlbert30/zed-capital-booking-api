<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserAttachment;
use Illuminate\Support\Facades\DB;

class ShareExistingAttachments extends Command
{
    protected $signature = 'attachments:share-existing';
    protected $description = 'Share existing handover documents (payment_proof, etc.) with co-owners';

    public function handle()
    {
        $this->info('Starting to share existing attachments with co-owners...');
        
        $sharedDocumentTypes = ['soa', 'payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc'];
        
        // Get all users
        $users = User::with('units', 'attachments')->get();
        $created = 0;
        
        foreach ($users as $user) {
            // Get user's shared attachments
            $sharedAttachments = $user->attachments()
                ->whereIn('type', $sharedDocumentTypes)
                ->get();
            
            if ($sharedAttachments->isEmpty()) {
                continue;
            }
            
            // Get all co-owners from the same units
            $unitIds = $user->units()->pluck('units.id');
            if ($unitIds->isEmpty()) {
                continue;
            }
            
            $coOwnerIds = DB::table('unit_user')
                ->whereIn('unit_id', $unitIds)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique();
            
            // Share each attachment with co-owners
            foreach ($sharedAttachments as $attachment) {
                foreach ($coOwnerIds as $coOwnerId) {
                    $coOwner = User::find($coOwnerId);
                    if (!$coOwner) {
                        continue;
                    }
                    
                    // Check if co-owner already has this attachment
                    $exists = $coOwner->attachments()
                        ->where('file_path', $attachment->file_path)
                        ->where('type', $attachment->type)
                        ->exists();
                    
                    if (!$exists) {
                        // Create attachment for co-owner
                        $coOwner->attachments()->create([
                            'unit_id' => $attachment->unit_id,
                            'filename' => $attachment->filename,
                            'file_path' => $attachment->file_path,
                            'type' => $attachment->type,
                        ]);
                        
                        $created++;
                        $this->info("Created {$attachment->type} attachment for user {$coOwner->email}");
                    }
                }
            }
        }
        
        $this->info("Done! Created {$created} shared attachment records.");
        return 0;
    }
}

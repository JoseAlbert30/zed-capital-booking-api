<?php

namespace App\Jobs;

use App\Models\Unit;
use App\Models\HandoverEmailBatch;
use App\Models\EmailLog;
use App\Models\UnitRemark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SendSOAEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $unitId;
    protected $adminName;
    protected $batchId;

    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($unitId, $adminName, $batchId)
    {
        $this->unitId = $unitId;
        $this->adminName = $adminName;
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("=== SOA EMAIL JOB STARTED ===", [
                'unit_id' => $this->unitId,
                'batch_id' => $this->batchId,
                'admin' => $this->adminName
            ]);

            // Load unit with relationships
            $unit = Unit::with(['users', 'property', 'attachments'])->findOrFail($this->unitId);

            // Get SOA attachment
            $soaAttachment = $unit->attachments->where('type', 'soa')->first();
            if (!$soaAttachment) {
                throw new \Exception('No SOA found for unit ' . $unit->unit);
            }

            // Determine the file path - handle both old and new attachment structures
            $filePath = null;
            if ($soaAttachment->file_path) {
                // New structure: file_path is stored directly
                $filePath = $soaAttachment->file_path;
            } else {
                // Old structure: construct path from property and unit
                $propertyName = $unit->property->project_name ?? 'Unknown';
                $unitNumber = $unit->unit;
                $filePath = "attachments/{$propertyName}/{$unitNumber}/{$soaAttachment->filename}";
            }

            if (!Storage::disk('public')->exists($filePath)) {
                Log::warning("SOA file not found at path: {$filePath}");
                throw new \Exception("SOA file not found for unit {$unit->unit}");
            }

            // Get all owners
            $allOwners = $unit->users;
            if ($allOwners->isEmpty()) {
                throw new \Exception('No owners found for unit ' . $unit->unit);
            }

            // Prepare first names for greeting
            $firstNames = $allOwners->map(function($owner) {
                return explode(' ', trim($owner->full_name))[0];
            })->toArray();

            // Format greeting
            if (count($firstNames) == 1) {
                $greeting = $firstNames[0];
            } elseif (count($firstNames) == 2) {
                $greeting = $firstNames[0] . ' & ' . $firstNames[1];
            } else {
                $lastNames = array_pop($firstNames);
                $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
            }

            $unitNumber = $unit->unit;
            $propertyName = $unit->property->project_name ?? 'Viera Residences';

            // Send email to all owners
            foreach ($allOwners as $owner) {
                if (!$owner->email) {
                    Log::warning("Owner {$owner->full_name} has no email address");
                    continue;
                }

                try {
                    Mail::send('emails.soa-payment-reminder', [
                        'firstName' => $greeting,
                        'unitNumber' => $unitNumber,
                        'propertyName' => $propertyName,
                    ], function ($message) use ($owner, $unit, $filePath, $unitNumber) {
                        $message->to($owner->email)
                                ->subject("Statement of Account - Unit {$unitNumber}, Viera Residences");

                        // Attach SOA PDF
                        if (Storage::disk('public')->exists($filePath)) {
                            $message->attach(Storage::disk('public')->path($filePath), [
                                'as' => "{$unitNumber}-soa.pdf",
                                'mime' => 'application/pdf'
                            ]);
                        }
                    });

                    // Log email
                    EmailLog::create([
                        'user_id' => $owner->id,
                        'unit_id' => $unit->id,
                        'subject' => "Statement of Account - Unit {$unitNumber}, Viera Residences",
                        'recipient_email' => $owner->email,
                        'recipient_name' => $owner->first_name . ' ' . $owner->last_name,
                        'message' => "SOA payment reminder sent for unit {$unitNumber}",
                        'type' => 'soa_payment_reminder',
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    Log::info("SOA email sent to {$owner->email} for unit {$unitNumber}");

                } catch (\Exception $e) {
                    Log::error("Failed to send SOA email to {$owner->email}: " . $e->getMessage());
                    
                    // Log failed email
                    EmailLog::create([
                        'user_id' => $owner->id,
                        'unit_id' => $unit->id,
                        'subject' => "Statement of Account - Unit {$unitNumber}, Viera Residences",
                        'recipient_email' => $owner->email,
                        'recipient_name' => $owner->first_name . ' ' . $owner->last_name,
                        'message' => "Failed to send SOA payment reminder for unit {$unitNumber}",
                        'type' => 'soa_payment_reminder',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'sent_at' => now(),
                    ]);
                }
            }

            // Add remark to unit timeline
            UnitRemark::create([
                'unit_id' => $unit->id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'SOA payment reminder email sent by ' . $this->adminName,
                'type' => 'email',
                'admin_name' => $this->adminName,
            ]);

            // Update batch progress
            $this->updateBatchProgress(true);

            Log::info("=== SOA EMAIL JOB COMPLETED ===", [
                'unit_id' => $this->unitId,
                'batch_id' => $this->batchId
            ]);

        } catch (\Exception $e) {
            Log::error("=== SOA EMAIL JOB FAILED ===", [
                'unit_id' => $this->unitId,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage()
            ]);

            // Update batch progress
            $this->updateBatchProgress(false);

            throw $e;
        }
    }

    /**
     * Update batch progress
     */
    protected function updateBatchProgress($success)
    {
        try {
            $batch = HandoverEmailBatch::where('batch_id', $this->batchId)->first();
            if (!$batch) {
                Log::warning("Batch {$this->batchId} not found");
                return;
            }

            if ($success) {
                $batch->increment('sent_count');
            } else {
                $batch->increment('failed_count');
                
                // Add unit to failed list
                $failedUnitIds = $batch->failed_unit_ids ?? [];
                if (!in_array($this->unitId, $failedUnitIds)) {
                    $failedUnitIds[] = $this->unitId;
                    $batch->failed_unit_ids = $failedUnitIds;
                }
            }

            // Check if batch is complete
            $totalProcessed = $batch->sent_count + $batch->failed_count;
            if ($totalProcessed >= $batch->total_emails) {
                $batch->status = 'completed';
                $batch->completed_at = now();
            }

            $batch->save();

        } catch (\Exception $e) {
            Log::error("Failed to update batch progress: " . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SOA EMAIL JOB PERMANENTLY FAILED", [
            'unit_id' => $this->unitId,
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage()
        ]);

        $this->updateBatchProgress(false);
    }
}

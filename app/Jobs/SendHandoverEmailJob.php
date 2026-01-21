<?php

namespace App\Jobs;

use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDF;

class SendHandoverEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $unitId;
    public $adminName;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($unitId, $adminName = 'System')
    {
        $this->unitId = $unitId;
        $this->adminName = $adminName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $unit = Unit::with(['users', 'attachments', 'property'])->findOrFail($this->unitId);

            // Check if SOA exists
            $soaAttachments = $unit->attachments->where('type', 'soa');
            if ($soaAttachments->isEmpty()) {
                Log::warning('Skipping handover email - No SOA found', [
                    'unit_id' => $this->unitId,
                    'unit' => $unit->unit
                ]);
                return;
            }

            // Get all owner emails
            $recipients = $unit->users->pluck('email')->toArray();
            
            if (empty($recipients)) {
                Log::warning('Skipping handover email - No owners found', [
                    'unit_id' => $this->unitId,
                    'unit' => $unit->unit
                ]);
                return;
            }

            // Get primary owner (or first owner)
            $primaryOwner = $unit->users->firstWhere('pivot.is_primary', true) ?? $unit->users->first();
            $firstName = explode(' ', $primaryOwner->full_name)[0];
            
            // Get SOA URL (using the first SOA attachment)
            $firstSOA = $soaAttachments->first();
            $soaUrl = $firstSOA ? $firstSOA->full_url : '#';

            // Generate Service Charge Acknowledgement PDF
            $owners = $unit->users;
            $date = $unit->handover_email_sent_at 
                ? \Carbon\Carbon::parse($unit->handover_email_sent_at)->format('F d, Y')
                : now()->format('F d, Y');
            
            $serviceChargePdf = PDF::loadView('pdfs.service-charge-acknowledgement', [
                'unit' => $unit,
                'owners' => $owners,
                'date' => $date
            ]);
            $serviceChargePdfContent = $serviceChargePdf->output();

            // Send email to all owners with SOA attachments
            Mail::send('emails.handover-notice', [
                'firstName' => $firstName,
                'soaUrl' => $soaUrl,
                'unit' => $unit,
                'property' => $unit->property,
            ], function($message) use ($recipients, $unit, $soaAttachments, $serviceChargePdfContent) {
                $message->to($recipients)
                    ->subject('Handover Notice - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                
                // Attach all SOA files
                foreach ($soaAttachments as $attachment) {
                    $filePath = storage_path('app/public/attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $attachment->filename);
                    if (file_exists($filePath)) {
                        $message->attach($filePath, [
                            'as' => $attachment->filename,
                            'mime' => 'application/pdf'
                        ]);
                    }
                }

                // Attach Service Charge Acknowledgement PDF
                $message->attachData($serviceChargePdfContent, 'Service_Charge_Acknowledgement_Unit_' . $unit->unit . '.pdf', [
                    'mime' => 'application/pdf'
                ]);

                // Attach handover notice PDFs from project-specific folder
                $projectSlug = strtolower(str_replace(' ', '-', $unit->property->project_name));
                $handoverDocumentsPath = storage_path('app/public/handover-notice-attachments/' . $projectSlug);
                
                if (is_dir($handoverDocumentsPath)) {
                    $files = glob($handoverDocumentsPath . '/*.pdf');
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            $message->attach($file, [
                                'as' => basename($file),
                                'mime' => 'application/pdf'
                            ]);
                        }
                    }
                }
            });

            // Mark handover email as sent
            $unit->handover_email_sent = true;
            $unit->handover_email_sent_at = now();
            $unit->save();

            // Add remark about handover email sent
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'Handover notice email sent to ' . count($recipients) . ' owner(s): ' . implode(', ', $recipients),
                'type' => 'email_sent',
                'admin_name' => $this->adminName,
            ]);

            Log::info('Handover email sent successfully', [
                'unit_id' => $this->unitId,
                'unit' => $unit->unit,
                'recipients_count' => count($recipients)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send handover email in job', [
                'unit_id' => $this->unitId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to allow job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Handover email job failed after retries', [
            'unit_id' => $this->unitId,
            'error' => $exception->getMessage()
        ]);
    }
}

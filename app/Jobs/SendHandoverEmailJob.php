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
    public $batchId;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($unitId, $adminName = 'System', $batchId = null)
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
            $unit = Unit::with(['users', 'attachments', 'property'])->findOrFail($this->unitId);

            // Check if SOA exists
            $soaAttachments = $unit->attachments->where('type', 'soa');
            if ($soaAttachments->isEmpty()) {
                return;
            }

            // Get all owner emails
            $recipients = $unit->users->pluck('email')->toArray();
            
            if (empty($recipients)) {
                return;
            }

            // Get primary owner (or first owner)
            $primaryOwner = $unit->users->firstWhere('pivot.is_primary', true) ?? $unit->users->first();
            $firstName = explode(' ', $primaryOwner->full_name)[0];
            
            // Get SOA URL (using the first SOA attachment)
            $firstSOA = $soaAttachments->first();
            if ($firstSOA && $firstSOA->unit) {
                $folderPath = 'attachments/' . $firstSOA->unit->property->project_name . '/' . $firstSOA->unit->unit;
                $soaUrl = url('storage/' . $folderPath . '/' . $firstSOA->filename);
            } else {
                $soaUrl = '#';
            }

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

            // Generate Utilities Registration Guide PDF with logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $utilitiesGuidePdf = PDF::loadView('utilities-registration-guide', [
                'dewaPremiseNumber' => $unit->dewa_premise_number ?? 'N/A',
                'logos' => [
                    'left' => $vieraLogo,
                    'right' => $vantageLogo,
                ]
            ]);
            $utilitiesGuidePdfContent = $utilitiesGuidePdf->output();

            // Save utilities guide PDF to storage using Laravel Storage
            $utilitiesGuideFilename = 'Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf';
            $storagePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $utilitiesGuideFilename;
            \Storage::disk('public')->put($storagePath, $utilitiesGuidePdfContent);

            // Save service charge acknowledgement PDF to storage
            $serviceChargeFilename = 'Service_Charge_Undertaking_Letter_Unit_' . $unit->unit . '.pdf';
            $serviceChargeStoragePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $serviceChargeFilename;
            \Storage::disk('public')->put($serviceChargeStoragePath, $serviceChargePdfContent);

            // Send email to all owners with SOA attachments
            Mail::send('emails.handover-notice', [
                'firstName' => $firstName,
                'soaUrl' => $soaUrl,
                'unit' => $unit,
                'property' => $unit->property,
            ], function($message) use ($recipients, $unit, $soaAttachments, $serviceChargePdfContent, $utilitiesGuidePdfContent) {
                $message->to($recipients)
                    ->subject('Handover Notice - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                
                // Request read and delivery receipts
                $message->withSymfonyMessage(function ($msg) {
                    $msg->getHeaders()->addTextHeader('Disposition-Notification-To', config('mail.from.address'));
                    $msg->getHeaders()->addTextHeader('Return-Receipt-To', config('mail.from.address'));
                });
                
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

                // Attach Utilities Registration Guide PDF
                $message->attachData($utilitiesGuidePdfContent, 'Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf', [
                    'mime' => 'application/pdf'
                ]);

                // Attach Escrow Account PDF
                $escrowPath = storage_path('app/public/handover-notice-attachments/viera-residences/Viera Residences - Escrow Acc.pdf');
                Log::info('Escrow path check', ['path' => $escrowPath, 'exists' => file_exists($escrowPath)]);
                if (file_exists($escrowPath)) {
                    $message->attach($escrowPath, [
                        'as' => 'Viera Residences - Escrow Acc.pdf',
                        'mime' => 'application/pdf'
                    ]);
                }

                // Attach RERA Inspection Report PDF
                $inspectionPath = storage_path('app/public/handover-notice-attachments/viera-residences/RERA_inspection_report.pdf');
                Log::info('Inspection path check', ['path' => $inspectionPath, 'exists' => file_exists($inspectionPath)]);
                if (file_exists($inspectionPath)) {
                    $message->attach($inspectionPath, [
                        'as' => 'RERA_inspection_report.pdf',
                        'mime' => 'application/pdf'
                    ]);
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

            // Update batch progress if batch_id provided
            if ($this->batchId) {
                $batch = \App\Models\HandoverEmailBatch::where('batch_id', $this->batchId)->first();
                if ($batch) {
                    $batch->incrementSent();
                }
            }


        } catch (\Exception $e) {
            // Update batch failed count if batch_id provided
            if ($this->batchId) {
                $batch = \App\Models\HandoverEmailBatch::where('batch_id', $this->batchId)->first();
                if ($batch) {
                    $batch->incrementFailed($this->unitId);
                }
            }

            // Re-throw to allow job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Update batch failed count if batch_id provided
        if ($this->batchId) {
            $batch = \App\Models\HandoverEmailBatch::where('batch_id', $this->batchId)->first();
            if ($batch) {
                $batch->incrementFailed();
            }
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Unit;
use App\Models\UserAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;

class GenerateSOAJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $unitId;
    public $batchId;
    public $adminName;
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct($unitId, $batchId = null, $adminName = 'System')
    {
        $this->unitId = $unitId;
        $this->batchId = $batchId;
        $this->adminName = $adminName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($this->unitId);

            // Delete existing SOA if it exists (for regeneration)
            $existingSOAs = $unit->attachments()->where('type', 'soa')->get();
            foreach ($existingSOAs as $existingSOA) {
                // Delete file from storage
                $propertyFolder = $unit->property->project_name;
                $unitFolder = $unit->unit;
                $filePath = "public/attachments/{$propertyFolder}/{$unitFolder}/{$existingSOA->filename}";
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                // Delete database record
                $existingSOA->delete();
            }

            // Get all owners
            $owners = $unit->users;
            if ($owners->isEmpty()) {
                
                if ($this->batchId) {
                    $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
                    if ($batch) {
                        $batch->incrementFailed();
                    }
                }
                return;
            }

            // Prepare owner names
            $ownerNames = $owners->pluck('full_name')->toArray();

            // Get logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            // Generate PDF with new template
            $pdf = PDF::loadView('pdfs.soa', [
                'unit' => $unit,
                'owners' => $owners,
                'property' => $unit->property,
                'logos' => [
                    'left' => $vieraLogo,
                    'right' => $vantageLogo
                ]
            ]);

            // Define storage path
            $propertyFolder = $unit->property->project_name;
            $unitFolder = $unit->unit;
            $filename = $unit->unit . '-soa.pdf';
            $storagePath = "attachments/{$propertyFolder}/{$unitFolder}/{$filename}";

            // Save PDF using Storage facade
            \Storage::disk('public')->put($storagePath, $pdf->output());

            // Create attachment record
            UserAttachment::create([
                'unit_id' => $unit->id,
                'filename' => $filename,
                'type' => 'soa',
            ]);

            // Add remark about SOA generation
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'SOA generated for ' . count($ownerNames) . ' owner(s): ' . implode(', ', $ownerNames),
                'type' => 'soa_generated',
                'admin_name' => $this->adminName,
            ]);

            // Update batch progress
            if ($this->batchId) {
                $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
                if ($batch) {
                    $batch->incrementGenerated();
                }
            }

        } catch (\Exception $e) {
            // Update batch failed count
            if ($this->batchId) {
                $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
                if ($batch) {
                    $batch->incrementFailed();
                }
            }

            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->batchId) {
            $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
            if ($batch) {
                $batch->incrementFailed();
            }
        }

    }
}

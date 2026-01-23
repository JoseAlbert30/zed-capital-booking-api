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
        Log::info("=== SOA JOB STARTED ===", [
            'unit_id' => $this->unitId,
            'batch_id' => $this->batchId,
            'admin_name' => $this->adminName
        ]);

        try {
            Log::info("Loading unit data", ['unit_id' => $this->unitId]);
            $unit = Unit::with(['users', 'property'])->findOrFail($this->unitId);
            
            Log::info("Unit loaded successfully", [
                'unit' => $unit->unit,
                'property' => $unit->property->project_name,
                'owners_count' => $unit->users->count()
            ]);

            // Delete existing SOA if it exists (for regeneration)
            $existingSOAs = $unit->attachments()->where('type', 'soa')->get();
            Log::info("Checking existing SOAs", ['count' => $existingSOAs->count()]);
            
            foreach ($existingSOAs as $existingSOA) {
                // Delete file from storage
                $propertyFolder = $unit->property->project_name;
                $unitFolder = $unit->unit;
                $filePath = "attachments/{$propertyFolder}/{$unitFolder}/{$existingSOA->filename}";
                
                Log::info("Deleting existing SOA", ['file_path' => $filePath]);
                
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                    Log::info("Deleted file from storage");
                }
                // Delete database record
                $existingSOA->delete();
                Log::info("Deleted database record");
            }

            // Get all owners
            $owners = $unit->users;
            if ($owners->isEmpty()) {
                Log::warning("No owners found for unit", ['unit_id' => $this->unitId]);
                
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
            Log::info("Preparing to generate SOA", ['owners' => $ownerNames]);

            // Get logos using Storage facade
            Log::info("Loading logos from storage");
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/viera-black.png'));
                Log::info("Viera logo loaded");
            } else {
                Log::warning("Viera logo not found");
            }
            
            if (Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/vantage-black.png'));
                Log::info("Vantage logo loaded");
            } else {
                Log::warning("Vantage logo not found");
            }

            // Generate PDF with new template
            Log::info("Generating PDF");
            $pdf = PDF::loadView('pdfs.soa', [
                'unit' => $unit,
                'owners' => $owners,
                'property' => $unit->property,
                'logos' => [
                    'left' => $vieraLogo,
                    'right' => $vantageLogo
                ]
            ]);
            Log::info("PDF generated successfully");

            // Define storage path
            $propertyFolder = $unit->property->project_name;
            $unitFolder = $unit->unit;
            $filename = $unit->unit . '-soa.pdf';
            $storagePath = "attachments/{$propertyFolder}/{$unitFolder}/{$filename}";
            
            Log::info("Saving PDF", ['storage_path' => $storagePath]);

            // Save PDF using Storage facade
            Storage::disk('public')->put($storagePath, $pdf->output());
            Log::info("PDF saved to storage");

            // Create attachment record
            Log::info("Creating attachment record");
            UserAttachment::create([
                'unit_id' => $unit->id,
                'filename' => $filename,
                'type' => 'soa',
            ]);
            Log::info("Attachment record created");

            // Add remark about SOA generation
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'SOA generated for ' . count($ownerNames) . ' owner(s): ' . implode(', ', $ownerNames),
                'type' => 'soa_generated',
                'admin_name' => $this->adminName,
            ]);
            Log::info("Remark added");

            // Update batch progress
            if ($this->batchId) {
                $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
                if ($batch) {
                    $batch->incrementGenerated();
                    Log::info("Batch progress updated", ['batch_id' => $this->batchId]);
                }
            }
            
            Log::info("=== SOA JOB COMPLETED SUCCESSFULLY ===", ['unit_id' => $this->unitId]);

        } catch (\Exception $e) {
            Log::error("=== SOA JOB FAILED ===", [
                'unit_id' => $this->unitId,
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
        Log::error("=== SOA JOB FAILED (failed method) ===", [
            'unit_id' => $this->unitId,
            'batch_id' => $this->batchId,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if ($this->batchId) {
            $batch = \App\Models\SoaGenerationBatch::where('batch_id', $this->batchId)->first();
            if ($batch) {
                $batch->incrementFailed();
                Log::info("Batch failed count incremented", ['batch_id' => $this->batchId]);
            }
        }

    }
}

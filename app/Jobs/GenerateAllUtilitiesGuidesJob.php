<?php

namespace App\Jobs;

use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GenerateAllUtilitiesGuidesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout
    public $tries = 1;

    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting utilities guides generation for batch {$this->batchId}");

            // Update batch status to processing
            $this->updateBatchStatus('processing', 0, 0);

            // Get all units with owners (claimed units)
            $units = Unit::with(['property', 'users'])
                ->whereHas('users')
                ->get();

            if ($units->isEmpty()) {
                $this->updateBatchStatus('failed', 0, 0, 'No units found');
                return;
            }

            // Create a temporary zip file
            $zipFileName = "all-utilities-guides-{$this->batchId}.zip";
            $zipFilePath = storage_path('app/temp/' . $zipFileName);
            
            // Create temp directory if it doesn't exist
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                $this->updateBatchStatus('failed', 0, 0, 'Could not create zip file');
                return;
            }

            // Get logos once (they're the same for all PDFs)
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $totalUnits = $units->count();
            $successCount = 0;
            $failedCount = 0;

            foreach ($units as $index => $unit) {
                try {
                    // Generate the utilities guide PDF for this unit
                    $pdf = \PDF::loadView('utilities-registration-guide', [
                        'dewaPremiseNumber' => $unit->dewa_premise_number ?? 'N/A',
                        'logos' => $logos,
                    ]);
                    
                    $propertyFolder = $unit->property->project_name;
                    $filename = "Utilities_Registration_Guide_Unit_{$unit->unit}.pdf";
                    
                    // Add PDF content directly to zip
                    $zipName = "{$propertyFolder}/{$filename}";
                    $zip->addFromString($zipName, $pdf->output());
                    $successCount++;

                    // Update progress every 5 units
                    if (($index + 1) % 5 === 0 || ($index + 1) === $totalUnits) {
                        $this->updateBatchStatus('processing', $successCount, $failedCount);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to generate utilities guide for unit {$unit->id}: " . $e->getMessage());
                    
                    // Update progress
                    if (($index + 1) % 5 === 0 || ($index + 1) === $totalUnits) {
                        $this->updateBatchStatus('processing', $successCount, $failedCount);
                    }
                    continue;
                }
            }

            $zip->close();

            if ($successCount === 0) {
                unlink($zipFilePath);
                $this->updateBatchStatus('failed', 0, $failedCount, 'No utilities guides could be generated');
                return;
            }

            // Move zip to public storage
            $publicPath = 'utilities-guides/' . $zipFileName;
            Storage::disk('public')->put($publicPath, file_get_contents($zipFilePath));
            unlink($zipFilePath);

            // Update batch status to completed
            $this->updateBatchStatus('completed', $successCount, $failedCount, null, $publicPath);

            Log::info("Completed utilities guides generation for batch {$this->batchId}. Success: {$successCount}, Failed: {$failedCount}");
        } catch (\Exception $e) {
            Log::error("Error in GenerateAllUtilitiesGuidesJob: " . $e->getMessage());
            $this->updateBatchStatus('failed', 0, 0, $e->getMessage());
        }
    }

    /**
     * Update batch status in cache
     */
    protected function updateBatchStatus($status, $successCount, $failedCount, $error = null, $filePath = null)
    {
        $data = [
            'batch_id' => $this->batchId,
            'status' => $status,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total_count' => $successCount + $failedCount,
            'updated_at' => now()->toIso8601String(),
        ];

        if ($error) {
            $data['error'] = $error;
        }

        if ($filePath) {
            $data['file_path'] = $filePath;
        }

        // Store in cache for 1 hour
        \Cache::put("utilities_guides_batch_{$this->batchId}", $data, now()->addHour());
    }
}

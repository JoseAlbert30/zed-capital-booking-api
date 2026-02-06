<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendOwnerHandoverEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    protected $unitId;
    protected $bookingId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $unitId, int $bookingId)
    {
        $this->unitId = $unitId;
        $this->bookingId = $bookingId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($this->unitId);
            $booking = \App\Models\Booking::findOrFail($this->bookingId);
            
            // Get all owners/co-owners of this unit
            $users = $unit->users;
            
            if ($users->isEmpty()) {
                Log::info('No users found for unit', [
                    'unit_id' => $this->unitId,
                    'booking_id' => $this->bookingId
                ]);
                return;
            }

            $property = $unit->property;
            $unitName = $unit->unit;

            foreach ($users as $user) {
                if (!$user->email) {
                    Log::warning('User has no email', [
                        'user_id' => $user->id,
                        'unit_id' => $this->unitId
                    ]);
                    continue;
                }

                Log::info('Sending handover congratulations email to owner', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'unit_id' => $this->unitId,
                    'booking_id' => $this->bookingId
                ]);

                Mail::send('emails.handover-congratulations', [
                    'userName' => $user->name,
                    'projectName' => $property->project_name,
                    'unitName' => $unitName,
                ], function ($message) use ($user, $property, $booking, $unitName) {
                    $message->to($user->email, $user->name)
                            ->subject('Congratulations on Your Unit Handover! - ' . $property->project_name);
                    
                    // Attach signed declaration PDF
                    if ($booking->handover_declaration) {
                        $declarationPath = Storage::disk('public')->path($booking->handover_declaration);
                        if (file_exists($declarationPath)) {
                            $message->attach($declarationPath, [
                                'as' => 'Signed_Declaration_Unit_' . $unitName . '.pdf',
                                'mime' => 'application/pdf',
                            ]);
                        }
                    }
                    
                    // Attach signed checklist PDF
                    if ($booking->handover_checklist) {
                        $checklistPath = Storage::disk('public')->path($booking->handover_checklist);
                        if (file_exists($checklistPath)) {
                            $message->attach($checklistPath, [
                                'as' => 'Signed_Checklist_Unit_' . $unitName . '.pdf',
                                'mime' => 'application/pdf',
                            ]);
                        }
                    }
                });

                // Log email
                EmailLog::create([
                    'user_id' => $user->id,
                    'email_type' => 'handover_congratulations',
                    'recipient_email' => $user->email,
                    'recipient_name' => $user->name ?? $user->full_name,
                    'subject' => 'Congratulations on Your Unit Handover! - ' . $property->project_name,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                Log::info('Handover congratulations email sent successfully', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'unit_id' => $this->unitId,
                    'booking_id' => $this->bookingId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send handover congratulations email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'unit_id' => $this->unitId,
                'booking_id' => $this->bookingId
            ]);
            
            // Re-throw the exception so the job can be retried
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendOwnerHandoverEmailJob failed after all retries', [
            'unit_id' => $this->unitId,
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

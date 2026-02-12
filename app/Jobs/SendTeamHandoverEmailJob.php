<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Remark;
use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendTeamHandoverEmailJob implements ShouldQueue
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
    protected $completedByUserId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $unitId, int $bookingId, int $completedByUserId)
    {
        $this->unitId = $unitId;
        $this->bookingId = $bookingId;
        $this->completedByUserId = $completedByUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($this->unitId);
            $booking = Booking::with('user')->findOrFail($this->bookingId);
            $completedByUser = \App\Models\User::findOrFail($this->completedByUserId);
            
            $allOwners = $unit->users;
            
            // Get co-owners information
            $coOwners = $allOwners->filter(function($owner) use ($booking) {
                return $owner->id !== $booking->user_id;
            })->map(function($owner) {
                return [
                    'name' => $owner->full_name,
                    'email' => $owner->email,
                ];
            })->values()->toArray();
            
            $completionDateTime = \Carbon\Carbon::parse($booking->handover_completed_at);
            $appointmentDate = \Carbon\Carbon::parse($booking->booked_date)->format('l, F j, Y');
            
            // Main recipients
            $mainRecipients = [
                'vantage@zedcapital.ae',    // Zed
                'docs@zedcapital.ae',        // Devi
                'admin@zedcapital.ae'        // Mayada
            ];
            
            // CC recipients (rest of the team)
            $ccRecipients = [
                'inquire@vantageventures.ae',
                'mtsen@evanlimpenta.com',
                'adham@evanlimpenta.com',
                'hani@bcoam.com',
                'clientsupport@zedcapital.ae',
                'operations@zedcapital.ae',
                'president@zedcapital.ae',
                'wbd@zedcapital.ae'
            ];
            
            Log::info('Sending handover completion email to team', [
                'unit_id' => $unit->id,
                'booking_id' => $booking->id,
                'main_recipients' => $mainRecipients,
                'cc_recipients' => $ccRecipients
            ]);
            
            Mail::send('emails.admin-handover-completed', [
                'propertyName' => $unit->property->project_name,
                'unitNumber' => $unit->unit,
                'completionDate' => $completionDateTime->format('l, F j, Y'),
                'completionTime' => $completionDateTime->format('g:i A'),
                'completedBy' => $completedByUser->full_name ?? $completedByUser->email,
                'customerName' => $booking->user->full_name,
                'customerEmail' => $booking->user->email,
                'customerMobile' => $booking->user->mobile_number,
                'coOwners' => $coOwners,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => \Carbon\Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A'),
            ], function ($mail) use ($booking, $unit, $mainRecipients, $ccRecipients) {
                // Send to main recipients
                $mail->to($mainRecipients);
                
                // CC the rest of the team
                $mail->cc($ccRecipients);
                
                $mail->subject('Handover Completed - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                
                // Attach declaration PDF
                if ($booking->handover_declaration) {
                    $declarationPath = Storage::disk('public')->path($booking->handover_declaration);
                    if (file_exists($declarationPath)) {
                        $mail->attach($declarationPath, [
                            'as' => 'Declaration_Unit_' . $unit->unit . '.pdf',
                            'mime' => 'application/pdf',
                        ]);
                    }
                }
                
                // Attach checklist PDF
                if ($booking->handover_checklist) {
                    $checklistPath = Storage::disk('public')->path($booking->handover_checklist);
                    if (file_exists($checklistPath)) {
                        $mail->attach($checklistPath, [
                            'as' => 'Checklist_Unit_' . $unit->unit . '.pdf',
                            'mime' => 'application/pdf',
                        ]);
                    }
                }
                
                // Attach handover photo
                if ($booking->handover_photo) {
                    $photoPath = Storage::disk('public')->path($booking->handover_photo);
                    if (file_exists($photoPath)) {
                        $extension = pathinfo($photoPath, PATHINFO_EXTENSION);
                        $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
                        $mail->attach($photoPath, [
                            'as' => 'Handover_Photo_Unit_' . $unit->unit . '.' . $extension,
                            'mime' => $mimeType,
                        ]);
                    }
                }
            });

            Log::info('Handover completion email sent successfully to team', [
                'unit_id' => $unit->id,
                'booking_id' => $booking->id
            ]);

            // Add remark for team notification email sent
            Remark::create([
                'unit_id' => $unit->id,
                'user_id' => $booking->user_id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'Handover completion notification email sent to team with attachments',
                'type' => 'email_sent',
                'admin_user_id' => $this->completedByUserId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send handover completion email to team', [
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
        Log::error('SendTeamHandoverEmailJob failed after all retries', [
            'unit_id' => $this->unitId,
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

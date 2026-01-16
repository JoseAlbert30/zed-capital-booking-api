<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Remark;
use App\Models\Unit;
use App\Models\EmailLog;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Booking::with(['user.units.property', 'unit']);

        // Exclude completed bookings by default
        $query->where('status', '!=', 'completed');

        // Search filter (by user name or email)
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function($q) use ($searchTerm) {
                $q->where('full_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Status filter (override the default if explicitly set)
        if ($request->has('status') && $request->status) {
            if ($request->status === 'all') {
                // Remove the default filter
                $query = Booking::with(['user.units.property', 'unit']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Project filter (through user's units)
        if ($request->has('project') && $request->project) {
            $query->whereHas('user.units.property', function($q) use ($request) {
                $q->where('project_name', $request->project);
            });
        }

        $bookings = $query->orderBy('booked_date', 'asc')->get();

        // Add co-owner information to each booking
        $bookings = $bookings->map(function($booking) {
            $user = $booking->user;
            if ($user && $user->units) {
                $unitIds = $user->units->pluck('id');
                
                // Get all co-owners
                $coOwnerIds = \DB::table('unit_user')
                    ->whereIn('unit_id', $unitIds)
                    ->pluck('user_id')
                    ->unique();
                
                $coOwners = \App\Models\User::whereIn('id', $coOwnerIds)
                    ->where('id', '!=', $user->id)
                    ->select('id', 'full_name', 'email')
                    ->get();
                
                $booking->co_owners = $coOwners;
            } else {
                $booking->co_owners = [];
            }
            return $booking;
        });

        return response()->json(['bookings' => $bookings]);
    }

    public function userBookings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $bookings = Booking::where('user_id', $user->id)
            ->with('unit.property')
            ->orderBy('booked_date', 'desc')
            ->get();

        return response()->json(['bookings' => $bookings]);
    }

    /**
     * Get units eligible for booking for the authenticated user
     */
    public function eligibleUnits(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get all units where user is an owner
        $units = $user->units()
            ->with(['property', 'bookings' => function($query) {
                $query->latest();
            }])
            ->where('payment_status', 'fully_paid')
            ->where('handover_ready', true)
            ->get();

        // Add booking status for each unit
        $units = $units->map(function($unit) {
            $existingBooking = $unit->bookings->first();
            $unit->has_booking = $existingBooking ? true : false;
            $unit->booking = $existingBooking;
            unset($unit->bookings); // Remove the collection, keep only the single booking
            return $unit;
        });

        return response()->json(['units' => $units]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        Log::info('Booking store request received', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'request_data' => $request->all()
        ]);

        // Validate input
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'booked_date' => 'required|date|after:today',
            'booked_time' => 'required|string|in:09:00,10:00,11:00,12:00,13:00,14:00,15:00,16:00,17:00,18:00,19:00,20:00',
        ]);

        if ($validator->fails()) {
            Log::warning('Booking validation failed', [
                'user_id' => $user->id,
                'errors' => $validator->errors()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify user owns this unit
        $unit = \App\Models\Unit::findOrFail($request->unit_id);
        if (!$unit->users->contains($user->id)) {
            return response()->json(['message' => 'You do not own this unit'], 403);
        }

        // Check if unit is eligible for booking
        if ($unit->payment_status !== 'fully_paid' || !$unit->handover_ready) {
            Log::warning('Unit not eligible for booking', [
                'user_id' => $user->id,
                'unit_id' => $unit->id,
                'payment_status' => $unit->payment_status,
                'handover_ready' => $unit->handover_ready
            ]);
            return response()->json(['message' => 'This unit is not eligible for booking yet. Payment must be completed and handover requirements fulfilled.'], 403);
        }

        Log::info('Unit eligibility check passed', [
            'user_id' => $user->id,
            'unit_id' => $unit->id
        ]);

        // Check if this unit already has a booking
        $existingBooking = Booking::where('unit_id', $request->unit_id)
            ->with('user')
            ->first();

        if ($existingBooking) {
            $bookedBy = $existingBooking->user_id === $user->id ? 'You' : $existingBooking->user->full_name;
            
            Log::warning('Existing booking found for unit', [
                'user_id' => $user->id,
                'unit_id' => $request->unit_id,
                'existing_booking_id' => $existingBooking->id,
                'booked_by' => $bookedBy
            ]);
            
            return response()->json([
                'message' => "A booking already exists for this unit. Booked by: {$bookedBy}",
                'booked_by' => $existingBooking->user->full_name,
                'existing_booking' => $existingBooking,
            ], 409);
        }

        // Check for time slot conflicts
        $conflictExists = Booking::where('booked_date', $request->booked_date)
            ->where('booked_time', $request->booked_time)
            ->exists();

        if ($conflictExists) {
            Log::warning('Time slot conflict', [
                'user_id' => $user->id,
                'date' => $request->booked_date,
                'time' => $request->booked_time
            ]);
            return response()->json(['message' => 'Time slot already booked'], 409);
        }

        Log::info('Creating booking', [
            'user_id' => $user->id,
            'booked_date' => $request->booked_date,
            'booked_time' => $request->booked_time
        ]);

        // Create booking
        $booking = Booking::create([
            'user_id' => $user->id,
            'unit_id' => $request->unit_id,
            'booked_date' => $request->booked_date,
            'booked_time' => $request->booked_time,
        ]);

        Log::info('Booking created successfully', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'unit_id' => $request->unit_id
        ]);

        // Add remark for booking creation to the unit
        $formattedDate = Carbon::parse($request->booked_date)->format('M d, Y');
        $unit->remarks()->create([
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'event' => "Handover appointment booked for {$formattedDate} at {$request->booked_time} by {$user->full_name}",
            'type' => 'booking_created',
            'admin_name' => $user->full_name,
        ]);

        // Send booking confirmation email
        try {
            // Get all owners of this unit
            $allOwners = $unit->users;
            
            // Prepare first names for greeting
            $firstNames = $allOwners->map(function($owner) {
                return explode(' ', trim($owner->full_name))[0];
            })->toArray();
            
            // Format the greeting
            if (count($firstNames) == 1) {
                $greeting = $firstNames[0];
            } elseif (count($firstNames) == 2) {
                $greeting = $firstNames[0] . ' & ' . $firstNames[1];
            } else {
                $lastNames = array_pop($firstNames);
                $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
            }
            
            $appointmentDate = Carbon::parse($request->booked_date)->format('l, F j, Y');
            $appointmentTime = $request->booked_time;
            
            // Send email to all owners
            Mail::send('emails.booking-confirmation', [
                'firstName' => $greeting,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => $appointmentTime,
                'unitNumber' => $unit->unit,
                'locationPin' => $unit->property->project_name . ', Dubai Land, Dubai',
                'unit' => $unit,
                'property' => $unit->property,
            ], function ($mail) use ($allOwners, $unit) {
                foreach ($allOwners as $owner) {
                    $mail->to($owner->email, $owner->full_name);
                }
                $mail->subject('Handover Appointment Confirmation - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
            });

            // Add remark for confirmation email sent to unit
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => "Booking confirmation email sent to " . count($allOwners) . " owner(s) for appointment on {$formattedDate} at {$request->booked_time}",
                'type' => 'email_sent',
                'admin_name' => 'System',
            ]);

            Log::info('Booking confirmation email sent', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'recipients' => $allOwners->pluck('email')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation email', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the booking if email fails
        }

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['booking' => $booking]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'booked_date' => 'sometimes|date',
            'booked_time' => 'sometimes|string|in:09:00,10:00,11:00,12:00,13:00,14:00,15:00,16:00,17:00,18:00,19:00,20:00',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Store old booking details for email
        $oldDate = $booking->booked_date;
        $oldTime = $booking->booked_time;
        $bookingUser = $booking->user;

        // Update the booking
        $booking->update($request->only(['booked_date', 'booked_time']));

        // Add remark for booking rescheduling
        $oldFormattedDate = Carbon::parse($oldDate)->format('M d, Y');
        $newFormattedDate = Carbon::parse($booking->booked_date)->format('M d, Y');
        
        $bookingUser->remarks()->create([
            'event' => "Handover appointment rescheduled from {$oldFormattedDate} at {$oldTime} to {$newFormattedDate} at {$booking->booked_time} (Admin)",
            'type' => 'booking_rescheduled',
            'date' => $booking->booked_date,
            'time' => $booking->booked_time,
            'admin_user_id' => $user->id,
        ]);

        // Send rescheduling email to all co-owners
        try {
            $unitIds = $bookingUser->units()->pluck('units.id');
            $coOwnerIds = \DB::table('unit_user')
                ->whereIn('unit_id', $unitIds)
                ->pluck('user_id')
                ->unique();
            
            $allOwners = \App\Models\User::whereIn('id', $coOwnerIds)->get();
            
            // Prepare first names for greeting
            $firstNames = $allOwners->map(function($owner) {
                return explode(' ', trim($owner->full_name))[0];
            })->toArray();
            
            // Format the greeting
            if (count($firstNames) == 1) {
                $greeting = $firstNames[0];
            } elseif (count($firstNames) == 2) {
                $greeting = $firstNames[0] . ' & ' . $firstNames[1];
            } else {
                $lastNames = array_pop($firstNames);
                $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
            }
            
            $oldAppointmentDate = Carbon::parse($oldDate)->format('l, F j, Y');
            $oldAppointmentTime = $oldTime;
            $newAppointmentDate = Carbon::parse($booking->booked_date)->format('l, F j, Y');
            $newAppointmentTime = $booking->booked_time;
            
            Mail::send('emails.booking-rescheduled', [
                'firstName' => $greeting,
                'oldAppointmentDate' => $oldAppointmentDate,
                'oldAppointmentTime' => $oldAppointmentTime,
                'newAppointmentDate' => $newAppointmentDate,
                'newAppointmentTime' => $newAppointmentTime,
            ], function ($mail) use ($allOwners) {
                foreach ($allOwners as $owner) {
                    $mail->to($owner->email, $owner->full_name);
                }
                $mail->subject('Handover Appointment Rescheduled - Viera Residences');
            });

            // Add remark for email sent
            foreach ($allOwners as $owner) {
                $owner->remarks()->create([
                    'event' => "Rescheduling notification email sent for new appointment on {$newFormattedDate} at {$booking->booked_time}",
                    'type' => 'reschedule_email_sent',
                    'date' => $booking->booked_date,
                    'time' => $booking->booked_time,
                ]);
            }

            Log::info('Booking rescheduling email sent', [
                'booking_id' => $booking->id,
                'recipients' => $allOwners->pluck('email')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send rescheduling email', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => $booking,
        ]);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email) && $booking->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Store booking details before deletion
        $bookedDate = $booking->booked_date;
        $bookedTime = $booking->booked_time;
        $isAdminCancellation = $this->isAdmin($user->email);

        // Get the booking owner's unit IDs
        $bookingUser = $booking->user;
        $unitIds = $bookingUser->units()->pluck('units.id');
        
        // Get all co-owners of the same units
        $coOwnerIds = \DB::table('unit_user')
            ->whereIn('unit_id', $unitIds)
            ->pluck('user_id')
            ->unique();
        
        $allOwners = \App\Models\User::whereIn('id', $coOwnerIds)->get();

        // Add remarks for cancellation
        $formattedDate = Carbon::parse($bookedDate)->format('M d, Y');
        $cancellationType = $isAdminCancellation ? '(Admin)' : '(Self)';
        
        foreach ($allOwners as $owner) {
            $owner->remarks()->create([
                'event' => "Handover appointment cancelled for {$formattedDate} at {$bookedTime} {$cancellationType}",
                'type' => 'booking_cancelled',
                'date' => $bookedDate,
                'time' => $bookedTime,
                'admin_user_id' => $isAdminCancellation ? $user->id : null,
            ]);
        }

        // Send cancellation email to all co-owners
        try {
            // Prepare first names for greeting
            $firstNames = $allOwners->map(function($owner) {
                return explode(' ', trim($owner->full_name))[0];
            })->toArray();
            
            // Format the greeting
            if (count($firstNames) == 1) {
                $greeting = $firstNames[0];
            } elseif (count($firstNames) == 2) {
                $greeting = $firstNames[0] . ' & ' . $firstNames[1];
            } else {
                $lastNames = array_pop($firstNames);
                $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
            }
            
            $appointmentDate = Carbon::parse($bookedDate)->format('l, F j, Y');
            $appointmentTime = $bookedTime;
            
            Mail::send('emails.booking-cancelled', [
                'firstName' => $greeting,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => $appointmentTime,
            ], function ($mail) use ($allOwners) {
                foreach ($allOwners as $owner) {
                    $mail->to($owner->email, $owner->full_name);
                }
                $mail->subject('Handover Appointment Cancelled - Viera Residences');
            });

            // Add remark for cancellation email sent
            foreach ($allOwners as $owner) {
                $owner->remarks()->create([
                    'event' => "Cancellation notification email sent for appointment on {$formattedDate} at {$bookedTime}",
                    'type' => 'cancellation_email_sent',
                    'date' => $bookedDate,
                    'time' => $bookedTime,
                ]);
            }

            Log::info('Booking cancellation email sent', [
                'booking_date' => $bookedDate,
                'recipients' => $allOwners->pluck('email')->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation email', [
                'booking_date' => $bookedDate,
                'error' => $e->getMessage()
            ]);
        }
        
        // Delete all bookings for co-owners with the same date and time
        Booking::whereIn('user_id', $coOwnerIds)
            ->where('booked_date', $bookedDate)
            ->where('booked_time', $bookedTime)
            ->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $availableSlots = $this->bookingService->getAvailableSlots($request->date);

        return response()->json(['available_slots' => $availableSlots]);
    }

    /**
     * Upload individual handover file
     */
    public function uploadHandoverFile(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file_type' => 'required|in:handover_checklist,handover_declaration,handover_photo,client_signature',
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $unit = $booking->unit;
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $fileType = $request->input('file_type');
            
            // Delete existing file of this type if it exists
            $existingAttachment = $unit->attachments()->where('type', $fileType)->first();
            if ($existingAttachment) {
                Storage::disk('public')->delete($folderPath . '/' . $existingAttachment->filename);
                $existingAttachment->delete();
            }

            // Save new file
            $file = $request->file('file');
            $extension = $file->extension();
            $filename = $fileType . '_' . time() . '.' . $extension;
            $path = $file->storeAs($folderPath, $filename, 'public');
            
            // Create attachment record
            $attachment = $unit->attachments()->create([
                'filename' => $filename,
                'type' => $fileType,
            ]);

            // Update booking record with path
            $booking->update([
                $fileType => $path,
            ]);

            // Add remark for file upload
            $fileTypeLabels = [
                'handover_checklist' => 'Handover Checklist',
                'handover_declaration' => 'Declaration V3',
                'handover_photo' => 'Handover Photo',
                'client_signature' => 'Client Signature',
            ];
            
            Remark::create([
                'unit_id' => $unit->id,
                'user_id' => $booking->user_id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => $fileTypeLabels[$fileType] . ' uploaded for booking #' . $booking->id,
                'type' => 'handover',
                'admin_user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'File uploaded successfully',
                'attachment' => $attachment,
                'path' => $path,
            ]);
        } catch (\Exception $e) {
            \Log::error('Handover file upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to upload file'], 500);
        }
    }

    /**
     * Delete individual handover file
     */
    public function deleteHandoverFile(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file_type' => 'required|in:handover_checklist,handover_declaration,handover_photo,client_signature',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $unit = $booking->unit;
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $fileType = $request->input('file_type');
            
            // Find and delete attachment
            $attachment = $unit->attachments()->where('type', $fileType)->first();
            if ($attachment) {
                Storage::disk('public')->delete($folderPath . '/' . $attachment->filename);
                $attachment->delete();
            }

            // Clear booking record
            $booking->update([
                $fileType => null,
            ]);

            // Add remark for file deletion
            $fileTypeLabels = [
                'handover_checklist' => 'Handover Checklist',
                'handover_declaration' => 'Declaration V3',
                'handover_photo' => 'Handover Photo',
                'client_signature' => 'Client Signature',
            ];
            
            Remark::create([
                'unit_id' => $unit->id,
                'user_id' => $booking->user_id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => $fileTypeLabels[$fileType] . ' deleted for booking #' . $booking->id,
                'type' => 'handover',
                'admin_user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Handover file deletion error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete file'], 500);
        }
    }

    /**
     * Complete a handover appointment (mark as completed after files uploaded)
     */
    public function completeHandover(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate that all files are uploaded
        if (!$booking->handover_checklist || !$booking->handover_declaration || 
            !$booking->handover_photo || !$booking->client_signature) {
            return response()->json([
                'message' => 'All handover files must be uploaded before completing'
            ], 422);
        }

        try {
            $unit = $booking->unit;

            // Update booking status
            $booking->update([
                'status' => 'completed',
                'handover_completed_at' => now(),
                'handover_completed_by' => Auth::id(),
            ]);

            // Update unit handover_status
            if ($unit) {
                $unit->update(['handover_status' => 'completed']);

                // Add remark to unit timeline with admin name
                $adminName = Auth::user()->name ?? Auth::user()->email;
                Remark::create([
                    'unit_id' => $unit->id,
                    'user_id' => $booking->user_id,
                    'date' => now()->toDateString(),
                    'time' => now()->toTimeString(),
                    'event' => 'Handover completed by ' . $adminName . ' for booking #' . $booking->id,
                    'type' => 'handover',
                    'admin_user_id' => Auth::id(),
                ]);

                // Send congratulations email to all owners/co-owners
                $this->sendCongratulationsEmail($unit);
            }

            // Add remark to user timeline
            $user = $booking->user;
            if ($user) {
                Remark::create([
                    'user_id' => $user->id,
                    'date' => now()->toDateString(),
                    'time' => now()->toTimeString(),
                    'event' => 'Handover completed for booking #' . $booking->id,
                    'type' => 'handover',
                    'admin_user_id' => Auth::id(),
                ]);
            }

            return response()->json([
                'message' => 'Handover completed successfully',
                'booking' => $booking->load('unit', 'user'),
            ]);
        } catch (\Exception $e) {
            \Log::error('Handover completion error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to complete handover'], 500);
        }
    }

    /**
     * Get template PDFs for a booking's project
     */
    public function getTemplates($id)
    {
        $booking = Booking::with('unit.property')->find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        $property = $booking->unit->property ?? null;

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        return response()->json([
            'handover_checklist_template' => $property->handover_checklist_template 
                ? url('api/storage/' . $property->handover_checklist_template) 
                : null,
            'declaration_template' => $property->declaration_template 
                ? url('api/storage/' . $property->declaration_template) 
                : null,
        ]);
    }

    private function isAdmin(string $email): bool
    {
        $adminEmails = config('app.admin_emails', []);
        return in_array($email, $adminEmails);
    }

    /**
     * Send congratulations email to all owners/co-owners of a unit
     */
    private function sendCongratulationsEmail($unit): void
    {
        try {
            // Get all owners/co-owners of this unit
            $users = $unit->users;
            
            if ($users->isEmpty()) {
                return;
            }

            $property = $unit->property;
            $unitName = $unit->unit;

            foreach ($users as $user) {
                if (!$user->email) {
                    continue;
                }

                Mail::send('emails.handover-congratulations', [
                    'userName' => $user->name,
                    'projectName' => $property->project_name,
                    'unitName' => $unitName,
                ], function ($message) use ($user, $property) {
                    $message->to($user->email, $user->name)
                            ->subject('Congratulations on Your Unit Handover! - ' . $property->project_name);
                });

                // Log email
                EmailLog::create([
                    'user_id' => $user->id,
                    'email_type' => 'handover_congratulations',
                    'recipient_email' => $user->email,
                    'subject' => 'Congratulations on Your Unit Handover! - ' . $property->project_name,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send congratulations email: ' . $e->getMessage());
            // Don't throw exception - handover should still complete even if email fails
        }
    }
}

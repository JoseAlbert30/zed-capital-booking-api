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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Remark;
use App\Models\Unit;
use App\Models\UnitRemark;
use App\Models\EmailLog;
use App\Models\SnaggingDefect;
use App\Jobs\SendOwnerHandoverEmailJob;
use App\Jobs\SendTeamHandoverEmailJob;

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

        $query = Booking::with(['user.units.property', 'unit', 'snaggingDefects']);

        // Exclude completed bookings by default
        $query->where('status', '!=', 'completed');

        // Search filter (by user name or email)
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('full_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        // Status filter (override the default if explicitly set)
        if ($request->has('status') && $request->status) {
            if ($request->status === 'all') {
                // Remove the default filter
                $query = Booking::with(['user.units.property', 'unit', 'snaggingDefects']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Project filter (through user's units)
        if ($request->has('project') && $request->project) {
            $query->whereHas('user.units.property', function ($q) use ($request) {
                $q->where('project_name', $request->project);
            });
        }

        $bookings = $query->orderBy('booked_date', 'asc')->get();

        // Add co-owner information to each booking
        $bookings = $bookings->map(function ($booking) {
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
            ->with(['property', 'bookings' => function ($query) {
                $query->latest();
            }])
            ->where('handover_ready', true)
            ->get();

        // Add booking status for each unit
        $units = $units->map(function ($unit) {
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

        // Convert is_owner_attending to boolean if it's a string, or set default
        if ($request->has('is_owner_attending')) {
            $isOwnerAttending = $request->input('is_owner_attending');
            if ($isOwnerAttending === '0' || $isOwnerAttending === 0 || $isOwnerAttending === false || $isOwnerAttending === 'false') {
                $request->merge(['is_owner_attending' => false]);
            } else {
                $request->merge(['is_owner_attending' => true]);
            }
        } else {
            // Default to true if not provided
            $request->merge(['is_owner_attending' => true]);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'unit_id' => 'required|exists:units,id',
            'booked_date' => 'required|date|after:today',
            'booked_time' => 'required|string|in:09:00,10:00,11:00,12:00,13:00,14:00,15:00,16:00,17:00',
            'is_owner_attending' => 'boolean',
            'poa_document' => 'required_if:is_owner_attending,false|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'attorney_id_document' => 'required_if:is_owner_attending,false|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'user_id' => 'required_if:created_by_admin,true|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify user owns this unit (skip for admin bookings on behalf)
        $unit = \App\Models\Unit::findOrFail($request->unit_id);
        $isAdminBooking = $request->created_by_admin && $this->isAdmin($user->email);

        // For admin bookings, verify the provided user_id owns the unit
        if ($isAdminBooking) {
            if (!$request->user_id || !$unit->users->contains($request->user_id)) {
                return response()->json(['message' => 'Invalid user_id for this unit'], 403);
            }
        } else if (!$unit->users->contains($user->id)) {
            return response()->json(['message' => 'You do not own this unit'], 403);
        }

        // Check if unit is eligible for booking
        if ($unit->payment_status !== 'fully_paid' || !$unit->handover_ready) {
            return response()->json(['message' => 'This unit is not eligible for booking yet. Payment must be completed and handover requirements fulfilled.'], 403);
        }

        // Check if this unit already has a booking
        $existingBooking = Booking::where('unit_id', $request->unit_id)
            ->with('user')
            ->first();

        if ($existingBooking) {
            $bookedBy = $existingBooking->user_id === $user->id ? 'You' : $existingBooking->user->full_name;

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
            return response()->json(['message' => 'Time slot already booked'], 409);
        }

        // Handle POA file uploads if owner is not attending
        $poaDocumentPath = null;
        $attorneyIdPath = null;
        // Use the converted boolean value from request (converted earlier in the method)
        $isOwnerAttending = $request->input('is_owner_attending');
        $bookingStatus = 'confirmed';

        \Log::info('POA Booking Debug', [
            'is_owner_attending_value' => $isOwnerAttending,
            'type' => gettype($isOwnerAttending),
            'strict_false_check' => $isOwnerAttending === false,
            'has_poa_doc' => $request->hasFile('poa_document'),
            'has_attorney_id' => $request->hasFile('attorney_id_document'),
        ]);

        if ($isOwnerAttending === false) {
            if ($request->hasFile('poa_document')) {
                $poaFile = $request->file('poa_document');
                $poaFileName = 'poa_' . time() . '_' . $poaFile->getClientOriginalName();
                $poaDocumentPath = $poaFile->storeAs('bookings/poa', $poaFileName, 'public');
            }

            if ($request->hasFile('attorney_id_document')) {
                $idFile = $request->file('attorney_id_document');
                $idFileName = 'attorney_id_' . time() . '_' . $idFile->getClientOriginalName();
                $attorneyIdPath = $idFile->storeAs('bookings/poa', $idFileName, 'public');
            }

            // Set status to pending approval if POA documents are uploaded
            $bookingStatus = 'pending_poa_approval';
            \Log::info('POA Booking: Status set to pending_poa_approval');
        } else {
            \Log::info('POA Booking: Owner attending, status remains confirmed');
        }

        // Create booking - use owner's ID if admin booking on behalf
        $bookingUserId = $isAdminBooking && $request->user_id ? $request->user_id : $user->id;
        $booking = Booking::create([
            'user_id' => $bookingUserId,
            'unit_id' => $request->unit_id,
            'booked_date' => $request->booked_date,
            'booked_time' => $request->booked_time,
            'is_owner_attending' => $isOwnerAttending,
            'poa_document' => $poaDocumentPath,
            'attorney_id_document' => $attorneyIdPath,
            'status' => $bookingStatus,
        ]);

        // Add remark for booking creation to the unit
        $formattedDate = Carbon::parse($request->booked_date)->format('M d, Y');
        $formattedTime = Carbon::createFromFormat('H:i', $request->booked_time)->format('g:i A');
        $attendanceStatus = $isOwnerAttending ? 'by owner' : 'with POA (pending approval)';
        $bookedByInfo = $isAdminBooking ? "by admin ({$user->full_name}) on behalf of owner" : "by owner";
        $unit->remarks()->create([
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'event' => "Handover appointment booked for {$formattedDate} at {$formattedTime} {$attendanceStatus} {$bookedByInfo}",
            'type' => 'booking_created',
            'admin_name' => $isAdminBooking ? $user->full_name : 'System',
        ]);

        // Send admin notification email
        try {
            $isPending = $bookingStatus === 'pending_poa_approval';
            $allOwners = $unit->users;

            // For admin bookings, use the booking owner's info; otherwise use authenticated user
            $bookingOwner = $isAdminBooking ? \App\Models\User::findOrFail($bookingUserId) : $user;

            // Get co-owners information
            $coOwners = $allOwners->filter(function ($owner) use ($bookingOwner) {
                return $owner->id !== $bookingOwner->id;
            })->map(function ($owner) {
                return [
                    'name' => $owner->full_name,
                    'email' => $owner->email,
                ];
            })->values()->toArray();

            $appointmentDate = Carbon::parse($request->booked_date)->format('l, F j, Y');
            $appointmentTime = Carbon::createFromFormat('H:i', $request->booked_time)->format('g:i A');

            Mail::send('emails.admin-booking-notification', [
                'isPending' => $isPending,
                'propertyName' => $unit->property->project_name,
                'unitNumber' => $unit->unit,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => $appointmentTime,
                'customerName' => $bookingOwner->full_name,
                'customerEmail' => $bookingOwner->email,
                'customerMobile' => $bookingOwner->mobile_number,
                'coOwners' => $coOwners,
            ], function ($mail) use ($isPending, $unit, $request) {
                $mail->to([
                    'operations@zedcapital.ae',
                    'docs@zedcapital.ae',
                    'admin@zedcapital.ae',
                    'clientsupport@zedcapital.ae',
                    'wbd@zedcapital.ae'
                ])
                    ->cc([
                        'inquire@vantageventures.ae',
                        'mtsen@evanlimpenta.com',
                        'adham@evanlimpenta.com',
                        'hani@bcoam.com',
                        'glen@evanlimpenta.com',
                        'badawi@evanlimpenta.com',
                        'info@bcoam.com',
                        'vantage@zedcapital.ae',
                        'president@zedcapital.ae'
                    ]);
                $mail->subject(
                    ($isPending ? '[ACTION REQUIRED] POA Approval Needed - ' : 'New Booking - ') .
                        'Unit ' . $unit->unit . ', ' . $unit->property->project_name
                );

                // Attach POA documents if they exist
                if ($isPending) {
                    if ($request->hasFile('poa_document')) {
                        $poaFile = $request->file('poa_document');
                        $mail->attach($poaFile->getRealPath(), [
                            'as' => 'POA_Document_' . $unit->unit . '.' . $poaFile->getClientOriginalExtension(),
                            'mime' => $poaFile->getMimeType(),
                        ]);
                    }

                    if ($request->hasFile('attorney_id_document')) {
                        $idFile = $request->file('attorney_id_document');
                        $mail->attach($idFile->getRealPath(), [
                            'as' => 'Representative_ID_' . $unit->unit . '.' . $idFile->getClientOriginalExtension(),
                            'mime' => $idFile->getMimeType(),
                        ]);
                    }
                }
            });

            // Add remark for admin notification email sent
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => "Admin notification email sent for " . ($isPending ? 'POA approval' : 'booking confirmation'),
                'type' => 'email_sent',
                'admin_name' => 'System',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send admin notification email: ' . $e->getMessage());
            // Don't fail the booking if email fails
        }

        // Send booking confirmation email only if status is confirmed (owner attending)
        if ($bookingStatus === 'confirmed') {
            try {
                // Get all owners of this unit
                $allOwners = $unit->users;

                \Log::info('Sending booking confirmation email', [
                    'booking_id' => $booking->id,
                    'unit_id' => $unit->id,
                    'owners_count' => count($allOwners),
                    'is_admin_booking' => $isAdminBooking,
                ]);

                // Prepare first names for greeting
                $firstNames = $allOwners->map(function ($owner) {
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
                $appointmentTime = Carbon::createFromFormat('H:i', $request->booked_time)->format('g:i A');

                // Send email to all owners
                Mail::send('emails.booking-confirmation', [
                    'firstName' => $greeting,
                    'appointmentDate' => $appointmentDate,
                    'appointmentTime' => $appointmentTime,
                    'unitNumber' => $unit->unit,
                    'locationPin' => $unit->property->project_name . ', Dubai Production City, Dubai',
                    'unit' => $unit,
                    'property' => $unit->property,
                ], function ($mail) use ($allOwners, $unit) {
                    foreach ($allOwners as $owner) {
                        $mail->to($owner->email, $owner->full_name);
                    }
                    $mail->bcc([
                        'inquire@vantageventures.ae',
                        'mtsen@evanlimpenta.com',
                        'adham@evanlimpenta.com',
                        'hani@bcoam.com',
                        'glen@evanlimpenta.com',
                        'badawi@evanlimpenta.com',
                        'info@bcoam.com',
                        'vantage@zedcapital.ae',
                        'docs@zedcapital.ae',
                        'admin@zedcapital.ae',
                        'clientsupport@zedcapital.ae',
                        'operations@zedcapital.ae',
                        'president@zedcapital.ae',
                        'wbd@zedcapital.ae'
                    ]);
                    $mail->subject('Handover Appointment Confirmation - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                });

                // Add remark for confirmation email sent to unit
                $confirmEmailTime = Carbon::createFromFormat('H:i', $request->booked_time)->format('g:i A');
                $unit->remarks()->create([
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i:s'),
                    'event' => "Booking confirmation email sent to " . count($allOwners) . " owner(s) for appointment on {$formattedDate} at {$confirmEmailTime}",
                    'type' => 'email_sent',
                    'admin_name' => 'System',
                ]);

                \Log::info('Booking confirmation email sent successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to send booking confirmation email: ' . $e->getMessage());
                // Don't fail the booking if email fails
            }
        } else {
            \Log::info('Booking confirmation email skipped', [
                'booking_status' => $bookingStatus,
                'expected' => 'confirmed',
            ]);
        }

        $message = $bookingStatus === 'pending_poa_approval'
            ? 'Booking submitted successfully. Your POA documents are under review. You will receive a confirmation email once approved.'
            : 'Booking created successfully';

        return response()->json([
            'message' => $message,
            'booking' => $booking,
            'status' => $bookingStatus,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load([
            'user' => function ($query) {
                $query->with(['units' => function ($q) {
                    $q->with('property');
                }]);
            },
            'unit' => function ($query) {
                $query->with(['property', 'users']);
            }
        ]);

        // Ensure unit.users is included in the response
        $bookingArray = $booking->toArray();
        if ($booking->unit && $booking->unit->users) {
            $bookingArray['unit']['users'] = $booking->unit->users->toArray();
        }

        return response()->json(['booking' => $bookingArray]);
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
        $oldFormattedTime = Carbon::createFromFormat('H:i', $oldTime)->format('g:i A');
        $newFormattedDate = Carbon::parse($booking->booked_date)->format('M d, Y');
        $newFormattedTime = Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A');

        $bookingUser->remarks()->create([
            'event' => "Handover appointment rescheduled from {$oldFormattedDate} at {$oldFormattedTime} to {$newFormattedDate} at {$newFormattedTime} (Admin)",
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
            $firstNames = $allOwners->map(function ($owner) {
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
            $oldAppointmentTime = Carbon::createFromFormat('H:i', $oldTime)->format('g:i A');
            $newAppointmentDate = Carbon::parse($booking->booked_date)->format('l, F j, Y');
            $newAppointmentTime = Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A');

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
                $mail->cc([
                    'inquire@vantageventures.ae',
                    'mtsen@evanlimpenta.com',
                    'adham@evanlimpenta.com',
                    'hani@bcoam.com',
                    'glen@evanlimpenta.com',
                    'badawi@evanlimpenta.com',
                    'info@bcoam.com',
                    'vantage@zedcapital.ae',
                    'docs@zedcapital.ae',
                    'admin@zedcapital.ae',
                    'clientsupport@zedcapital.ae',
                    'operations@zedcapital.ae',
                    'president@zedcapital.ae',
                    'wbd@zedcapital.ae'
                ]);
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
        } catch (\Exception $e) {
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
            $firstNames = $allOwners->map(function ($owner) {
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
            $appointmentTime = Carbon::createFromFormat('H:i', $bookedTime)->format('g:i A');

            Mail::send('emails.booking-cancelled', [
                'firstName' => $greeting,
                'appointmentDate' => $appointmentDate,
                'appointmentTime' => $appointmentTime,
            ], function ($mail) use ($allOwners) {
                foreach ($allOwners as $owner) {
                    $mail->to($owner->email, $owner->full_name);
                }
                $mail->cc([
                    'inquire@vantageventures.ae',
                    'mtsen@evanlimpenta.com',
                    'adham@evanlimpenta.com',
                    'hani@bcoam.com',
                    'glen@evanlimpenta.com',
                    'badawi@evanlimpenta.com',
                    'info@bcoam.com',
                    'vantage@zedcapital.ae',
                    'docs@zedcapital.ae',
                    'admin@zedcapital.ae',
                    'clientsupport@zedcapital.ae',
                    'operations@zedcapital.ae',
                    'president@zedcapital.ae',
                    'wbd@zedcapital.ae'
                ]);
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
        } catch (\Exception $e) {
        }

        // Delete all bookings for co-owners with the same date and time
        Booking::whereIn('user_id', $coOwnerIds)
            ->where('booked_date', $bookedDate)
            ->where('booked_time', $bookedTime)
            ->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }

    /**
     * Approve or reject a POA booking
     */
    public function approvePoaBooking(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($booking->status !== 'pending_poa_approval') {
            return response()->json(['message' => 'This booking is not pending POA approval'], 400);
        }

        $action = $request->input('action');
        $unit = $booking->unit;

        if ($action === 'approve') {
            // Approve the booking
            $booking->status = 'confirmed';
            $booking->save();

            // Add remark for POA approval
            $formattedDate = Carbon::parse($booking->booked_date)->format('M d, Y');
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => "POA booking approved for appointment on {$formattedDate} at " . Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A') . " - Approved by {$user->full_name}",
                'type' => 'poa_approved',
                'admin_name' => $user->full_name,
            ]);

            // Send confirmation email to all owners
            try {
                $allOwners = $unit->users;

                // Prepare first names for greeting
                $firstNames = $allOwners->map(function ($owner) {
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

                $appointmentDate = Carbon::parse($booking->booked_date)->format('l, F j, Y');
                $appointmentTime = Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A');

                // Send confirmation email
                Mail::send('emails.booking-confirmation', [
                    'firstName' => $greeting,
                    'appointmentDate' => $appointmentDate,
                    'appointmentTime' => $appointmentTime,
                    'unitNumber' => $unit->unit,
                    'locationPin' => $unit->property->project_name . ', Dubai Production City, Dubai',
                    'unit' => $unit,
                    'property' => $unit->property,
                    'isPOA' => true,
                ], function ($mail) use ($allOwners, $unit) {
                    foreach ($allOwners as $owner) {
                        $mail->to($owner->email, $owner->full_name);
                    }
                    $mail->cc([
                        'inquire@vantageventures.ae',
                        'mtsen@evanlimpenta.com',
                        'adham@evanlimpenta.com',
                        'hani@bcoam.com',
                        'glen@evanlimpenta.com',
                        'badawi@evanlimpenta.com',
                        'info@bcoam.com',
                        'vantage@zedcapital.ae',
                        'docs@zedcapital.ae',
                        'admin@zedcapital.ae',
                        'clientsupport@zedcapital.ae',
                        'operations@zedcapital.ae',
                        'president@zedcapital.ae',
                        'wbd@zedcapital.ae'
                    ]);
                    $mail->subject('Handover Appointment Confirmed (POA Approved) - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                });

                // Add remark for confirmation email sent
                $unit->remarks()->create([
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i:s'),
                    'event' => "POA approval confirmation email sent to " . count($allOwners) . " owner(s) for appointment on {$formattedDate} at {$booking->booked_time}",
                    'type' => 'email_sent',
                    'admin_name' => 'System',
                ]);
            } catch (\Exception $e) {
                // Don't fail if email fails
            }

            return response()->json([
                'message' => 'POA booking approved and confirmation email sent',
                'booking' => $booking,
            ]);
        } else {
            // Reject the booking
            $rejectionReason = $request->input('rejection_reason');

            // Add remark for POA rejection
            $formattedDate = Carbon::parse($booking->booked_date)->format('M d, Y');
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => "POA booking rejected for appointment on {$formattedDate} at {$booking->booked_time} - Rejected by {$user->full_name}. Reason: {$rejectionReason}",
                'type' => 'poa_rejected',
                'admin_name' => $user->full_name,
            ]);

            // Send rejection email to all owners
            try {
                $allOwners = $unit->users;

                $firstNames = $allOwners->map(function ($owner) {
                    return explode(' ', trim($owner->full_name))[0];
                })->toArray();

                if (count($firstNames) == 1) {
                    $greeting = $firstNames[0];
                } elseif (count($firstNames) == 2) {
                    $greeting = $firstNames[0] . ' & ' . $firstNames[1];
                } else {
                    $lastNames = array_pop($firstNames);
                    $greeting = implode(', ', $firstNames) . ', & ' . $lastNames;
                }

                $appointmentDate = Carbon::parse($booking->booked_date)->format('l, F j, Y');
                $appointmentTime = Carbon::createFromFormat('H:i', $booking->booked_time)->format('g:i A');

                \Log::info('Sending POA rejection email', [
                    'unit_id' => $unit->id,
                    'booking_id' => $booking->id,
                    'rejection_reason' => $rejectionReason,
                    'recipients' => $allOwners->pluck('email')->toArray()
                ]);

                // Send rejection email
                Mail::send('emails.poa-rejection', [
                    'firstName' => $greeting,
                    'appointmentDate' => $appointmentDate,
                    'appointmentTime' => $appointmentTime,
                    'unitNumber' => $unit->unit,
                    'rejectionReason' => $rejectionReason,
                    'unit' => $unit,
                    'property' => $unit->property,
                ], function ($mail) use ($allOwners, $unit) {
                    foreach ($allOwners as $owner) {
                        $mail->to($owner->email, $owner->full_name);
                    }
                    $mail->cc([
                        'inquire@vantageventures.ae',
                        'mtsen@evanlimpenta.com',
                        'adham@evanlimpenta.com',
                        'hani@bcoam.com',
                        'glen@evanlimpenta.com',
                        'badawi@evanlimpenta.com',
                        'info@bcoam.com',
                        'vantage@zedcapital.ae',
                        'docs@zedcapital.ae',
                        'admin@zedcapital.ae',
                        'clientsupport@zedcapital.ae',
                        'operations@zedcapital.ae',
                        'president@zedcapital.ae',
                        'wbd@zedcapital.ae'
                    ]);
                    $mail->subject('Handover Appointment POA Rejected - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                });

                \Log::info('POA rejection email sent successfully', [
                    'unit_id' => $unit->id,
                    'booking_id' => $booking->id
                ]);

                // Add remark for rejection email sent
                $unit->remarks()->create([
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i:s'),
                    'event' => "POA rejection email sent to " . count($allOwners) . " owner(s) for appointment on {$formattedDate} at {$booking->booked_time}",
                    'type' => 'email_sent',
                    'admin_name' => 'System',
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to send POA rejection email', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'unit_id' => $unit->id ?? null,
                    'booking_id' => $booking->id ?? null
                ]);
                // Don't fail the rejection if email fails
            }

            // Delete the booking
            $booking->delete();

            return response()->json([
                'message' => 'POA booking rejected and notification sent',
            ]);
        }
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

        // Validate that all required files are uploaded
        if (
            !$booking->handover_checklist || !$booking->handover_declaration ||
            !$booking->handover_photo
        ) {
            return response()->json([
                'message' => 'All handover files must be uploaded before completing'
            ], 422);
        }

        // Note: Snagging defects validation removed - defects can exist as long as declaration is signed

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

           
                SendOwnerHandoverEmailJob::dispatch($unit->id, $booking->id);

                SendTeamHandoverEmailJob::dispatch($unit->id, $booking->id, Auth::id())
                    ->delay(now()->addSeconds(5));
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

    /**
     * Get all snagging defects for a booking
     */
    public function getSnaggingDefects(Booking $booking): JsonResponse
    {
        $user = request()->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $defects = $booking->snaggingDefects()->with('creator')->orderBy('created_at', 'asc')->get();

        return response()->json(['defects' => $defects]);
    }

    /**
     * Create a snagging defect for a booking
     */
    public function createSnaggingDefect(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|file|mimes:jpeg,jpg,png|max:10240',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'agreed_remediation_action' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $unit = $booking->unit;
            $path = null;

            // Save image if provided
            if ($request->hasFile('image')) {
                // Sanitize folder path to remove special characters
                $projectName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $unit->property->project_name);
                $unitName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $unit->unit);
                $folderPath = 'snagging/' . $projectName . '/' . $unitName;

                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = 'defect_' . time() . '_' . uniqid() . '.' . $extension;

                // Use Storage::put which automatically creates directories
                $fullPath = $folderPath . '/' . $filename;
                $stored = Storage::disk('public')->put($fullPath, file_get_contents($file->getRealPath()));

                if (!$stored) {
                    throw new \Exception('Failed to store image file');
                }

                $path = $fullPath;
            }

            // Create defect record
            $defect = $booking->snaggingDefects()->create([
                'image_path' => $path,
                'description' => $request->input('description'),
                'location' => $request->input('location'),
                'agreed_remediation_action' => $request->input('agreed_remediation_action'),
                'created_by' => Auth::id(),
            ]);

            // Add remark to unit timeline
            $adminName = Auth::user()->name ?? Auth::user()->email;
            UnitRemark::create([
                'unit_id' => $unit->id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'Snagging defect added' . ($request->input('location') ? ' at ' . $request->input('location') : '') . ': ' . ($request->input('description') ?: 'No description'),
                'type' => 'snagging',
                'admin_name' => $adminName,
            ]);

            return response()->json([
                'message' => 'Snagging defect created successfully',
                'defect' => $defect->load('creator'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create defect',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a snagging defect
     */
    public function updateSnaggingDefect(Request $request, Booking $booking, SnaggingDefect $defect): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify defect belongs to this booking
        if ($defect->booking_id !== $booking->id) {
            return response()->json(['message' => 'Defect does not belong to this booking'], 403);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'agreed_remediation_action' => 'nullable|string',
            'is_remediated' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $defect->update($request->only(['description', 'location', 'agreed_remediation_action', 'is_remediated']));

            // Add remark to unit timeline
            $unit = $booking->unit;
            $adminName = Auth::user()->name ?? Auth::user()->email;
            UnitRemark::create([
                'unit_id' => $unit->id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'Snagging defect updated' . ($request->input('location') ? ' at ' . $request->input('location') : '') . ': ' . ($request->input('description') ?: 'No description'),
                'type' => 'snagging',
                'admin_name' => $adminName,
            ]);

            return response()->json([
                'message' => 'Snagging defect updated successfully',
                'defect' => $defect->load('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update defect'], 500);
        }
    }

    /**
     * Delete a snagging defect
     */
    public function deleteSnaggingDefect(Request $request, Booking $booking, SnaggingDefect $defect): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify defect belongs to this booking
        if ($defect->booking_id !== $booking->id) {
            return response()->json(['message' => 'Defect does not belong to this booking'], 403);
        }

        try {
            // Get defect info before deletion
            $defectInfo = $defect->description ?: 'No description';
            $defectLocation = $defect->location;

            // Delete the image file
            if ($defect->image_path) {
                Storage::disk('public')->delete($defect->image_path);
            }

            $defect->delete();

            // Add remark to unit timeline
            $unit = $booking->unit;
            $adminName = Auth::user()->name ?? Auth::user()->email;
            UnitRemark::create([
                'unit_id' => $unit->id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'Snagging defect deleted' . ($defectLocation ? ' at ' . $defectLocation : '') . ': ' . $defectInfo,
                'type' => 'snagging',
                'admin_name' => $adminName,
            ]);

            return response()->json([
                'message' => 'Snagging defect deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete defect'], 500);
        }
    }

    private function isAdmin(string $email): bool
    {
        $adminEmails = config('app.admin_emails', []);
        return in_array($email, $adminEmails);
    }



    /**
     * Save declaration signatures incrementally
     */
    public function saveDeclarationSignatures(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'part' => 'required|in:1,2,3',
            'signatures' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $part = $request->input('part');
        $signatures = $request->input('signatures');

        try {
            $field = "declaration_part{$part}_signatures";
            $booking->$field = $signatures;
            $booking->save();

            return response()->json([
                'message' => 'Signatures saved successfully',
                'part' => $part
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save signatures'
            ], 500);
        }
    }

    /**
     * Generate Declaration PDF with snagging defects
     */
    public function generateDeclaration(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $booking->load([
                'user' => function ($query) {
                    $query->with(['units' => function ($q) {
                        $q->with('property');
                    }]);
                },
                'unit' => function ($query) {
                    $query->with(['property', 'users']);
                }
            ]);

            $defects = SnaggingDefect::where('booking_id', $booking->id)->get();

            // Convert defect images to base64 using Storage facade
            foreach ($defects as $defect) {
                if ($defect->image_path) {
                    if (\Storage::disk('public')->exists($defect->image_path)) {
                        $imageData = \Storage::disk('public')->get($defect->image_path);
                        $extension = pathinfo($defect->image_path, PATHINFO_EXTENSION);
                        $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
                        $defect->image_base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    }
                }
            }

            // Get co-owners
            $coOwners = $booking->unit->users->where('id', '!=', $booking->user_id)->values();

            // Get signature data from request
            $signatureName = $request->input('signature_name', '');
            $signatureImage = $request->input('signature_image', '');
            $signaturesData = $request->input('signatures_data', null);

            // Save signatures to booking (only Part 1 and Part 2)
            if ($signaturesData) {
                if (isset($signaturesData['part1']) && !empty($signaturesData['part1'])) {
                    $booking->declaration_part1_signatures = $signaturesData['part1'];
                }
                if (isset($signaturesData['part2']) && !empty($signaturesData['part2'])) {
                    $booking->declaration_part2_signatures = $signaturesData['part2'];
                }
                $booking->save();
            }

            // Convert letterhead images to base64 using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            $footerBanner = '';

            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/footer-banner.png')) {
                $footerBanner = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/footer-banner.png'));
            }

            // Prepare data arrays for template
            $seller = [
                'name' => 'Vantage Ventures Real Estate Development L.L.C.',
                'address' => 'Office 12F-A-04, Empire Heights A, Business Bay, Dubai, UAE',
                'phone' => '+971 58 898 0456',
                'email' => 'vantage@zedcapital.ae'
            ];

            $purchaser = [
                'name' => $booking->user->full_name ?? '',
                'address' => $booking->user->address ?? 'N/A',
                'phone' => $booking->user->mobile_number ?? '',
                'email' => $booking->user->email ?? ''
            ];

            $property = [
                'master_community' => 'VIERA Residences, Dubai Production City, Dubai',
                'building' => $booking->unit->building ?? $booking->unit->property->building_name ?? '',
                'unit_number' => $booking->unit->unit ?? ''
            ];

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $date = now()->format('d M Y');
            // Generate HTML content for PDF
            $html = view('declaration-pdf-v3', [
                'date' => $date,
                'seller' => $seller,
                'purchaser' => $purchaser,
                'property' => $property,
                'logos' => $logos,
                'defects' => $defects,
                'coOwners' => $coOwners,
                'signatureName' => $signatureName,
                'signatureImage' => $signatureImage,
                'signaturesData' => $signaturesData
            ])->render();

            // Generate PDF using Dompdf
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Return PDF as base64 for frontend handling
            return response()->json([
                'success' => true,
                'pdf_content' => base64_encode($pdf->output()),
                'filename' => 'Declaration_' . $booking->id . '_' . now()->format('Ymd_His') . '.pdf'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate declaration PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateHandoverChecklist(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $booking->load([
                'user' => function ($query) {
                    $query->with(['units' => function ($q) {
                        $q->with('property');
                    }]);
                },
                'unit' => function ($query) {
                    $query->with(['property', 'users']);
                }
            ]);

            // Get form data from request
            $formData = $request->all();

            // Get co-owners
            $coOwners = $booking->unit->users->where('id', '!=', $booking->user_id)->values();

            // Convert letterhead images to base64 using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            $footerBanner = '';

            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/footer-banner.png')) {
                $footerBanner = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/footer-banner.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $date = now()->format('d M Y');

            // Generate HTML content for PDF
            $html = view('handover-checklist-pdf', [
                'date' => $date,
                'booking' => $booking,
                'purchaser' => $booking->user,
                'unit' => $booking->unit,
                'property' => $booking->unit->property,
                'coOwners' => $coOwners,
                'logos' => $logos,
                'formData' => $formData
            ])->render();

            // Generate PDF using Dompdf
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Save PDF directly to storage
            $unit = $booking->unit;
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $filename = 'handover_checklist_' . time() . '.pdf';
            $storagePath = $folderPath . '/' . $filename;

            // Delete existing handover checklist if it exists
            $existingAttachment = $unit->attachments()->where('type', 'handover_checklist')->first();
            if ($existingAttachment) {
                \Storage::disk('public')->delete($folderPath . '/' . $existingAttachment->filename);
                $existingAttachment->delete();
            }

            // Save the PDF
            \Storage::disk('public')->put($storagePath, $pdf->output());

            // Create attachment record
            $attachment = $unit->attachments()->create([
                'filename' => $filename,
                'type' => 'handover_checklist',
            ]);

            // Update booking record with path
            $booking->update([
                'handover_checklist' => $storagePath,
            ]);

            // Add remark for file generation
            Remark::create([
                'unit_id' => $unit->id,
                'user_id' => $booking->user_id,
                'date' => now()->toDateString(),
                'time' => now()->toTimeString(),
                'event' => 'Handover Checklist generated and saved for booking #' . $booking->id,
                'type' => 'handover',
                'admin_user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Handover checklist generated and saved successfully',
                'attachment' => $attachment,
                'path' => $storagePath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate handover checklist PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadBlankDeclarationTemplate(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Convert letterhead images to base64 using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';

            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            // Prepare blank data for template
            $seller = [
                'name' => 'Vantage Ventures Real Estate Development L.L.C.',
                'address' => 'Office 12F-A-04, Empire Heights A, Business Bay, Dubai, UAE',
                'phone' => '+971 58 898 0456',
                'email' => 'vantage@zedcapital.ae'
            ];

            $purchaser = [
                'name' => '', // Blank
                'address' => '',
                'phone' => '',
                'email' => ''
            ];

            // Empty co-owners for blank template
            $coOwners = collect();

            $property = [
                'master_community' => 'VIERA Residences, Dubai Production City, Dubai',
                'building' => '',
                'unit_number' => ''
            ];

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $date = '';  // Blank date

            // Empty defects array for blank template
            $defects = [];

            // Generate HTML content for PDF with blank fields
            $html = view('declaration-pdf-v3', [
                'date' => $date,
                'seller' => $seller,
                'purchaser' => $purchaser,
                'property' => $property,
                'logos' => $logos,
                'defects' => $defects,
                'coOwners' => $coOwners,
                'signatureName' => '',
                'signatureImage' => '',
                'signaturesData' => null,
                'isBlankTemplate' => true  // Flag to indicate this is a blank template
            ])->render();

            // Generate PDF using Dompdf
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Return PDF as download
            return $pdf->download('Declaration_Template_Blank.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate blank declaration template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadBlankChecklistTemplate(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->user_id !== $user->id && !$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            // Convert letterhead images to base64 using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';

            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }

            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $date = '';  // Blank date

            // Empty form data for blank template
            $formData = [];

            // Empty co-owners for blank template
            $coOwners = collect();

            // Create blank purchaser object
            $blankPurchaser = (object) [
                'full_name' => '',
                'email' => '',
                'phone' => ''
            ];

            // Create blank unit object
            $blankUnit = (object) [
                'unit' => ''
            ];

            // Create blank property object
            $blankProperty = (object) [
                'project_name' => 'VIERA Residences',
                'location' => 'Dubai Production City, Dubai'
            ];

            // Generate HTML content for PDF with blank fields
            $html = view('handover-checklist-pdf', [
                'date' => $date,
                'booking' => null,
                'purchaser' => $blankPurchaser,
                'unit' => $blankUnit,
                'property' => $blankProperty,
                'coOwners' => $coOwners,
                'logos' => $logos,
                'formData' => $formData,
                'isBlankTemplate' => true  // Flag to indicate this is a blank template
            ])->render();

            // Generate PDF using Dompdf
            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            // Return PDF as download
            return $pdf->download('Handover_Checklist_Template_Blank.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate blank checklist template',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

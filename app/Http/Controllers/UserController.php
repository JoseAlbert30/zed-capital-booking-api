<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Remark;
use App\Models\MagicLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->isAdmin($user->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $adminEmails = config('app.admin_emails', []);
        $query = User::with(['units.property', 'units.users', 'remarks.admin', 'attachments', 'bookings'])
            ->withCount('bookings')
            ->whereNotIn('email', $adminEmails);

        // Search filter (name, email, or unit number)
        if ($request->has('search') && $request->search) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('full_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhereHas('units', function($unitQuery) use ($searchTerm) {
                      $unitQuery->where('unit_number', 'like', $searchTerm);
                  });
            });
        }

        // Payment status filter
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Project filter
        if ($request->has('project') && $request->project) {
            $query->whereHas('units.property', function($q) use ($request) {
                $q->where('project_name', $request->project);
            });
        }

        $users = $query->get();

        return response()->json(['users' => $users]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['units.property', 'attachments']);
        
        // Get all unit IDs owned by this user (including co-ownership)
        $unitIds = $user->units()->pluck('units.id');
        
        // Get all co-owners of the same units
        $coOwnerIds = DB::table('unit_user')
            ->whereIn('unit_id', $unitIds)
            ->pluck('user_id')
            ->unique();
        
        // Get all bookings made by any co-owner
        $allBookings = \App\Models\Booking::whereIn('user_id', $coOwnerIds)
            ->with('user:id,full_name,email')
            ->orderBy('booked_date', 'desc')
            ->get()
            ->map(function($booking) use ($user) {
                $booking->booked_by_name = $booking->user->full_name;
                $booking->booked_by_email = $booking->user->email;
                $booking->is_own_booking = $booking->user_id === $user->id;
                return $booking;
            });
        
        $user->bookings = $allBookings;
        
        return response()->json(['user' => $user]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'mobile_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($request->only(['full_name', 'email', 'mobile_number']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function updatePaymentStatus(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->isAdmin($authUser->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|in:pending,partial,fully_paid',
            'receipt' => 'required_if:payment_status,fully_paid|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentDate = $request->payment_status === 'fully_paid' ? now() : null;
        $oldStatus = $user->payment_status;
        
        $user->update([
            'payment_status' => $request->payment_status,
            'payment_date' => $paymentDate,
        ]);

        // Log payment status change to timeline
        $this->addRemarkToUser($user, 
            'Payment status updated from ' . strtoupper($oldStatus) . ' to ' . strtoupper($request->payment_status),
            'payment_update'
        );

        // Handle receipt upload for fully_paid status
        if ($request->payment_status === 'fully_paid' && $request->hasFile('receipt')) {
            $file = $request->file('receipt');
            
            // Get the user's primary unit
            $unit = $user->units()->first();
            if ($unit) {
                $filename = 'receipt_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $directory = 'attachments/unit_' . $unit->id;
                $path = $file->storeAs($directory, $filename, 'public');

                // Create attachment record
                $user->attachments()->create([
                    'unit_id' => $unit->id,
                    'filename' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'type' => 'payment_proof',
                ]);
            }
        }

        // If payment is fully_paid, update all co-owners to fully_paid as well
        if ($request->payment_status === 'fully_paid') {
            $unitIds = $user->units()->pluck('units.id');
            
            // Get all co-owners of the same units
            $coOwnerIds = DB::table('unit_user')
                ->whereIn('unit_id', $unitIds)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique();
            
            // Update all co-owners to fully_paid
            $coOwners = User::whereIn('id', $coOwnerIds)->get();
            foreach ($coOwners as $coOwner) {
                $coOwner->update([
                    'payment_status' => 'fully_paid',
                    'payment_date' => $paymentDate,
                ]);
                
                // Log to co-owner's timeline
                $this->addRemarkToUser($coOwner, 
                    'Payment status automatically updated to FULLY PAID (co-owner payment completed)',
                    'payment_update'
                );
            }
        }

        return response()->json([
            'message' => 'Payment status updated successfully',
            'user' => $user->load(['attachments', 'units.property']),
        ]);
    }

    public function regeneratePassword(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->isAdmin($authUser->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $newPassword = Str::random(12);
        
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return response()->json([
            'message' => 'Password regenerated successfully',
            'user' => $user,
            'temporary_password' => $newPassword,
        ]);
    }

    public function getUserByEmail(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->isAdmin($authUser->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
            ->with('bookings')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->isAdmin($authUser->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::with(['units.property', 'units.users', 'attachments', 'bookings', 'remarks.admin'])
            ->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Transform remarks to include admin_name for frontend compatibility
        $remarksWithAdminNames = $user->remarks->map(function ($remark) {
            $remarkData = $remark->toArray();
            $remarkData['admin_name'] = $remark->admin ? $remark->admin->full_name : 'System';
            unset($remarkData['admin_user_id']); // Remove internal ID from API response
            return $remarkData;
        });

        $userData = $user->toArray();
        $userData['remarks'] = $remarksWithAdminNames;

        return response()->json(['user' => $userData]);
    }

    private function isAdmin(string $email): bool
    {
        $adminEmails = config('app.admin_emails', []);
        return in_array($email, $adminEmails);
    }

    /**
     * Add a manual remark/note to a user's timeline
     */
    public function addRemark(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if (!$this->isAdmin($authUser->email)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'remark' => 'required|string|max:1000',
        ]);

        $this->addRemarkToUser($user, $request->remark, 'manual_note');

        // Fetch remarks with admin relationship and transform for frontend
        $remarksWithAdminNames = $user->remarks()->with('admin')->get()->map(function ($remark) {
            $remarkData = $remark->toArray();
            $remarkData['admin_name'] = $remark->admin ? $remark->admin->full_name : 'System';
            unset($remarkData['admin_user_id']);
            return $remarkData;
        });

        return response()->json([
            'message' => 'Remark added successfully',
            'remarks' => $remarksWithAdminNames,
        ]);
    }

    /**
     * Upload attachment for user
     */
    public function uploadAttachment(Request $request, User $user)
    {
        if (!$request->user() || !in_array($request->user()->email, config('app.admin_emails', []))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'type' => 'required|string|in:soa,contract,id,passport,emirates_id,visa,receipt,other,payment_proof,ac_connection,dewa_connection,service_charge_ack,developer_noc,bank_noc',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->type;
            
            // Get the user's primary unit (first unit)
            $unit = $user->units()->first();
            if (!$unit) {
                return response()->json(['message' => 'User has no associated unit'], 400);
            }
            
            // Generate filename with timestamp to prevent duplicates
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = $originalName . '_' . time() . '.' . $extension;
            
            // Store file in unit-specific directory: attachments/unit_{id}/filename.ext
            $directory = 'attachments/unit_' . $unit->id;
            $path = $file->storeAs($directory, $filename, 'public');
            
            // Create attachment record
            $attachment = $user->attachments()->create([
                'unit_id' => $unit->id,
                'filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'type' => $type,
            ]);

            // Handover documents that should be shared with co-owners
            $sharedDocumentTypes = ['soa', 'payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc'];

            // Add remark for document upload
            $remarkText = ucfirst(str_replace('_', ' ', $type)) . ' document uploaded: ' . $file->getClientOriginalName();
            $this->addRemarkToUser($user, $remarkText, 'system', $request->user()?->id);
            
            // If this is a shared handover document, also create attachment record for all co-owners
            if (in_array($type, $sharedDocumentTypes)) {
                // Get all co-owners from the same units
                $unitIds = $user->units()->pluck('units.id');
                $coOwnerIds = DB::table('unit_user')
                    ->whereIn('unit_id', $unitIds)
                    ->where('user_id', '!=', $user->id)
                    ->pluck('user_id')
                    ->unique();
                
                // Create same attachment for each co-owner
                foreach ($coOwnerIds as $coOwnerId) {
                    $coOwner = User::find($coOwnerId);
                    if ($coOwner) {
                        // Co-owners share the same file, stored in the unit directory
                        $coOwner->attachments()->create([
                            'unit_id' => $unit->id,
                            'filename' => $file->getClientOriginalName(),
                            'file_path' => $path,  // Same file path
                            'type' => $type,
                        ]);
                        $coOwnerRemarkText = ucfirst(str_replace('_', ' ', $type)) . ' document uploaded (co-owner): ' . $file->getClientOriginalName();
                        $this->addRemarkToUser($coOwner, $coOwnerRemarkText, 'system', $request->user()?->id);
                    }
                }
                
                // Check if all handover requirements are met and update handover_ready status
                $this->updateHandoverReadyStatus($user);
                foreach ($coOwnerIds as $coOwnerId) {
                    $coOwner = User::find($coOwnerId);
                    if ($coOwner) {
                        $this->updateHandoverReadyStatus($coOwner);
                    }
                }
            }

            return response()->json([
                'message' => 'File uploaded successfully',
                'attachment' => $attachment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a timeline entry to user's remarks
     * 
     * @param User $user
     * @param string $event Event description
     * @param string $type Event type (e.g., 'payment_update', 'booking_confirmed', 'handover_confirmed', 'manual_note')
     * @return void
     */
    private function addRemarkToUser(User $user, string $event, string $type = 'system', ?int $adminUserId = null): void
    {
        Remark::create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'event' => $event,
            'type' => $type,
            'admin_user_id' => $adminUserId ?? ($type === 'system' ? null : auth()->user()?->id)
        ]);
    }

    /**
     * Check and update handover ready status based on uploaded documents
     * 
     * @param User $user
     * @return void
     */
    private function updateHandoverReadyStatus(User $user): void
    {
        // Get all attachment types for this user
        $attachmentTypes = $user->attachments()->pluck('type')->unique()->toArray();
        
        // Required documents for handover
        $requiredDocs = ['payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc'];
        
        // If user has mortgage, bank NOC is also required
        if ($user->has_mortgage) {
            $requiredDocs[] = 'bank_noc';
        }
        
        // Check if all required documents are uploaded
        $allUploaded = true;
        foreach ($requiredDocs as $doc) {
            if (!in_array($doc, $attachmentTypes)) {
                $allUploaded = false;
                break;
            }
        }
        
        // Update handover_ready status if it changed
        if ($user->handover_ready !== $allUploaded) {
            $user->handover_ready = $allUploaded;
            $user->save();
            
            // Add remark
            if ($allUploaded) {
                $this->addRemarkToUser($user, 'All handover requirements completed - User is ready for booking platform access', 'system', auth()->user()?->id);
            }
        }
    }

    /**
     * Get handover requirements status for a user
     */
    public function getHandoverStatus(Request $request, User $user)
    {
        if (!$request->user() || !in_array($request->user()->email, config('app.admin_emails', []))) {
            // Allow users to check their own status
            if (!$request->user() || $request->user()->id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Get all attachment types for this user
        $attachmentTypes = $user->attachments()->pluck('type')->unique()->toArray();
        
        // Required documents for handover
        $requiredDocs = [
            'payment_proof' => 'Payment Proof',
            'ac_connection' => 'AC Connection Documents',
            'dewa_connection' => 'DEWA Connection Documents',
            'service_charge_ack' => 'Service Charge Acknowledgement',
            'developer_noc' => 'Developer Handover NOC',
        ];
        
        // If user has mortgage, bank NOC is also required
        if ($user->has_mortgage) {
            $requiredDocs['bank_noc'] = 'Bank Handover NOC';
        }
        
        // Build status array
        $requirements = [];
        foreach ($requiredDocs as $type => $label) {
            $requirements[] = [
                'type' => $type,
                'label' => $label,
                'uploaded' => in_array($type, $attachmentTypes),
                'required' => true,
            ];
        }
        
        return response()->json([
            'handover_ready' => $user->handover_ready,
            'has_mortgage' => $user->has_mortgage,
            'requirements' => $requirements,
        ]);
    }

    /**
     * Update mortgage status for a user
     */
    public function updateMortgageStatus(Request $request, User $user)
    {
        if (!$request->user() || !in_array($request->user()->email, config('app.admin_emails', []))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'has_mortgage' => 'required|boolean',
        ]);

        $oldStatus = $user->has_mortgage;
        $user->has_mortgage = $request->has_mortgage;
        $user->save();

        // Update handover ready status based on new mortgage requirement
        $this->updateHandoverReadyStatus($user);

        // Add remark
        $statusText = $request->has_mortgage ? 'has mortgage' : 'no mortgage';
        $this->addRemarkToUser($user, "Mortgage status updated: {$statusText}", 'system', $request->user()?->id);

        // Update mortgage status for all co-owners
        $unitIds = $user->units()->pluck('units.id');
        if ($unitIds->isNotEmpty()) {
            $coOwnerIds = DB::table('unit_user')
                ->whereIn('unit_id', $unitIds)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique();
            
            foreach ($coOwnerIds as $coOwnerId) {
                $coOwner = User::find($coOwnerId);
                if ($coOwner && $coOwner->has_mortgage !== $request->has_mortgage) {
                    $coOwner->has_mortgage = $request->has_mortgage;
                    $coOwner->save();
                    
                    // Update handover ready status for co-owner
                    $this->updateHandoverReadyStatus($coOwner);
                    
                    // Add remark for co-owner
                    $this->addRemarkToUser($coOwner, "Mortgage status updated: {$statusText} (co-owner)", 'system', $request->user()?->id);
                }
            }
        }

        return response()->json([
            'message' => 'Mortgage status updated successfully for all co-owners',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Send booking platform access link via email
     */
    public function sendBookingLink(Request $request, User $user)
    {
        if (!$request->user() || !in_array($request->user()->email, config('app.admin_emails', []))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user is ready for handover
        if (!$user->handover_ready) {
            return response()->json([
                'message' => 'User has not completed all handover requirements',
                'handover_ready' => false,
            ], 400);
        }

        try {
            // Get all co-owners
            $coOwners = collect();
            foreach ($user->units as $unit) {
                foreach ($unit->users as $coOwner) {
                    if ($coOwner->id !== $user->id) {
                        $coOwners->push($coOwner);
                    }
                }
            }
            $coOwners = $coOwners->unique('id');

            // Combine primary user and co-owners
            $allRecipients = collect([$user])->merge($coOwners);

            // Find the first eligible unit for this user to include in the link
            $firstEligibleUnit = $user->units()
                ->where('payment_status', 'fully_paid')
                ->where('handover_ready', true)
                ->first();

            // Generate magic link for each recipient and send email
            $magicLinks = [];
            foreach ($allRecipients as $index => $recipient) {
                // Generate magic link (valid for 72 hours)
                $magicLink = MagicLink::generate($recipient, 72);
                
                // Include unit_id in booking URL if available
                $bookingUrl = config('app.frontend_url') . '/booking?token=' . $magicLink->token;
                if ($firstEligibleUnit) {
                    $bookingUrl .= '&unit_id=' . $firstEligibleUnit->id;
                }

                // Send email (create simple template for now)
                Mail::send('emails.booking-link', [
                    'firstName' => explode(' ', trim($recipient->full_name))[0],
                    'bookingUrl' => $bookingUrl,
                    'unit' => $firstEligibleUnit,
                    'property' => $firstEligibleUnit ? $firstEligibleUnit->property : null,
                ], function ($mail) use ($recipient) {
                    $mail->to($recipient->email, $recipient->full_name);
                    $mail->subject('Welcome to Viera Residences Booking Platform');
                });

                $magicLinks[] = [
                    'email' => $recipient->email,
                    'token' => $magicLink->token,
                    'expires_at' => $magicLink->expires_at,
                ];

                // Add remark
                $this->addRemarkToUser($recipient, 'Booking platform access link sent via email', 'system', $request->user()?->id);

                // Add small delay between emails to avoid rate limiting (except for last recipient)
                if ($index < $allRecipients->count() - 1) {
                    usleep(500000); // 0.5 second delay
                }
            }

            return response()->json([
                'message' => 'Booking links sent successfully',
                'recipients' => $allRecipients->count(),
                'links' => $magicLinks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send booking links',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a user attachment
     */
    public function deleteAttachment(Request $request, User $user, $attachmentId)
    {
        if (!$request->user() || !in_array($request->user()->email, config('app.admin_emails', []))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attachment = $user->attachments()->find($attachmentId);
        
        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $attachmentType = $attachment->type;
        $attachmentFilename = $attachment->filename;
        $attachmentFilePath = $attachment->file_path;

        // Handover documents that are shared with co-owners
        $sharedDocumentTypes = ['soa', 'payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc'];

        // If this is a shared document, delete it from all co-owners too
        if (in_array($attachmentType, $sharedDocumentTypes)) {
            // Get all co-owners from the same units
            $unitIds = $user->units()->pluck('units.id');
            $coOwnerIds = DB::table('unit_user')
                ->whereIn('unit_id', $unitIds)
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->unique();
            
            // Delete attachment from all co-owners
            foreach ($coOwnerIds as $coOwnerId) {
                $coOwner = User::find($coOwnerId);
                if ($coOwner) {
                    // Find and delete the same attachment (by file_path and type)
                    $coOwnerAttachment = $coOwner->attachments()
                        ->where('file_path', $attachmentFilePath)
                        ->where('type', $attachmentType)
                        ->first();
                    
                    if ($coOwnerAttachment) {
                        $coOwnerAttachment->delete();
                        $remarkText = ucfirst(str_replace('_', ' ', $attachmentType)) . ' document deleted (co-owner): ' . $attachmentFilename;
                        $this->addRemarkToUser($coOwner, $remarkText, 'system', $request->user()?->id);
                        
                        // Update handover ready status for co-owner
                        $this->updateHandoverReadyStatus($coOwner);
                    }
                }
            }
        }

        // Delete the physical file from public storage
        if ($attachmentFilePath && Storage::disk('public')->exists($attachmentFilePath)) {
            Storage::disk('public')->delete($attachmentFilePath);
        }

        // Delete the database record
        $attachment->delete();

        // Add remark
        $remarkText = ucfirst(str_replace('_', ' ', $attachmentType)) . ' document deleted: ' . $attachmentFilename;
        $this->addRemarkToUser($user, $remarkText, 'system', $request->user()?->id);

        // Update handover ready status if it's a handover document
        $handoverTypes = ['payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack', 'developer_noc', 'bank_noc'];
        if (in_array($attachmentType, $handoverTypes)) {
            $this->updateHandoverReadyStatus($user);
        }

        return response()->json([
            'message' => 'Attachment deleted successfully'
        ]);
    }

    /**
     * View/download an attachment file
     */
    public function viewAttachment(Request $request, User $user, $attachmentId)
    {
        $attachment = $user->attachments()->find($attachmentId);
        
        if (!$attachment || !$attachment->file_path) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        // Check if file exists in storage
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return response()->json(['message' => 'File not found on disk'], 404);
        }

        // Get the file
        $file = Storage::disk('public')->get($attachment->file_path);
        $path = Storage::disk('public')->path($attachment->file_path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . $attachment->filename . '"');
    }

    /**
     * Create a new user and assign to a unit.
     */
    public function createWithUnit(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_id' => 'required|exists:units,id',
                'full_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'mobile_number' => 'nullable|string|max:20',
                'is_primary' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Generate random password
                $password = Str::random(12);

                // Create user
                $user = User::create([
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'mobile_number' => $request->mobile_number,
                    'password' => Hash::make($password),
                ]);

                // Assign user to unit
                $isPrimary = $request->is_primary ?? false;
                $user->units()->attach($request->unit_id, ['is_primary' => $isPrimary]);

                // Update unit status to claimed
                $unit = \App\Models\Unit::find($request->unit_id);
                $unit->update(['status' => 'claimed']);

                // Email sending disabled for bulk operations
                // Credentials will be provided manually to users

                DB::commit();

                $user->load(['units.property', 'units.users', 'bookings']);

                return response()->json([
                    'success' => true,
                    'message' => 'Client created successfully',
                    'user' => $user,
                    'password' => $password
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create client',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk upload users from CSV/Excel file.
     * Expected CSV format: full_name, email, mobile_number, unit_number, property_name, is_primary
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:properties,id',
                'file' => 'required|file|mimes:csv,txt,xlsx|max:10240' // 10MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $results = [
                'total' => 0,
                'created' => 0,
                'skipped' => 0,
                'errors' => []
            ];

            DB::beginTransaction();

            try {
                // Get property to use building name
                $property = \App\Models\Property::find($request->property_id);
                if (!$property) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Property not found'
                    ], 404);
                }
                
                if ($extension === 'csv' || $extension === 'txt') {
                    // Parse CSV
                    $handle = fopen($file->getRealPath(), 'r');
                    
                    // Skip first header row
                    $header1 = fgetcsv($handle);
                    // Skip second header row
                    $header2 = fgetcsv($handle);
                    
                    while (($row = fgetcsv($handle)) !== false) {
                        $results['total']++;
                        
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        
                        // Expected format: S.NO, Unit No, DEWA Premise No, Status, Buyer 1 Name, Passport, Contact, Email, Buyer 2 Name, Passport, Contact, Email
                        if (count($row) < 12) {
                            $results['errors'][] = "Row {$results['total']}: Invalid format - expected 12 columns";
                            $results['skipped']++;
                            continue;
                        }

                        $unitNumber = trim($row[1]);
                        $dewaPremiseNumber = trim($row[2]);
                        $status = trim($row[3]);
                        $buyer1Name = trim($row[4]);
                        $buyer1Email = trim($row[7]);
                        $buyer2Name = isset($row[8]) ? trim($row[8]) : '';
                        $buyer2Email = isset($row[11]) ? trim($row[11]) : '';
                        
                        // Skip if no buyers (Available/Blocked status)
                        if (empty($buyer1Name) && empty($buyer1Email)) {
                            continue;
                        }

                        // Extract floor from unit number (first digit(s))
                        $floor = '';
                        if (strlen($unitNumber) >= 2) {
                            $floor = substr($unitNumber, 0, -2); // All digits except last 2
                        }

                        // Find or create unit
                        $unit = \App\Models\Unit::where('property_id', $request->property_id)
                            ->where('unit', $unitNumber)
                            ->first();

                        if (!$unit) {
                            // Create unit if it doesn't exist
                            $unit = \App\Models\Unit::create([
                                'property_id' => $request->property_id,
                                'unit' => $unitNumber,
                                'floor' => $floor,
                                'building' => $property->project_name,
                                'dewa_premise_number' => $dewaPremiseNumber,
                                'status' => 'unclaimed'
                            ]);
                        } else {
                            // Update DEWA premise number, floor, and building if unit exists
                            $unit->update([
                                'dewa_premise_number' => $dewaPremiseNumber,
                                'floor' => $floor,
                                'building' => $property->project_name
                            ]);
                        }

                        // Process Buyer 1
                        if (!empty($buyer1Name) && !empty($buyer1Email)) {
                            // Validate email format
                            if (!filter_var($buyer1Email, FILTER_VALIDATE_EMAIL)) {
                                $results['errors'][] = "Row {$results['total']}: Invalid buyer 1 email format";
                                $results['skipped']++;
                                continue;
                            }

                            // Check if user already exists
                            $existingUser = User::where('email', $buyer1Email)->first();
                            if (!$existingUser) {
                                // Generate random password
                                $password = Str::random(12);

                                // Create user
                                $user = User::create([
                                    'full_name' => $buyer1Name,
                                    'email' => $buyer1Email,
                                    'password' => Hash::make($password),
                                ]);

                                // Assign user to unit as primary
                                $user->units()->attach($unit->id, ['is_primary' => true]);

                                // Update unit status to claimed
                                $unit->update(['status' => 'claimed']);

                                $results['created']++;
                            } else {
                                // User exists, just link to unit if not already linked
                                if (!$existingUser->units()->where('unit_id', $unit->id)->exists()) {
                                    $existingUser->units()->attach($unit->id, ['is_primary' => true]);
                                    $unit->update(['status' => 'claimed']);
                                }
                            }
                        }

                        // Process Buyer 2 (co-owner)
                        if (!empty($buyer2Name) && !empty($buyer2Email)) {
                            // Validate email format
                            if (!filter_var($buyer2Email, FILTER_VALIDATE_EMAIL)) {
                                $results['errors'][] = "Row {$results['total']}: Invalid buyer 2 email format";
                                continue; // Don't skip the whole row, just buyer 2
                            }

                            // Check if user already exists
                            $existingUser2 = User::where('email', $buyer2Email)->first();
                            if (!$existingUser2) {
                                // Generate random password
                                $password = Str::random(12);

                                // Create user
                                $user2 = User::create([
                                    'full_name' => $buyer2Name,
                                    'email' => $buyer2Email,
                                    'password' => Hash::make($password),
                                ]);

                                // Assign user to unit as co-owner (not primary)
                                $user2->units()->attach($unit->id, ['is_primary' => false]);

                                $results['created']++;
                            } else {
                                // User exists, just link to unit if not already linked
                                if (!$existingUser2->units()->where('unit_id', $unit->id)->exists()) {
                                    $existingUser2->units()->attach($unit->id, ['is_primary' => false]);
                                }
                            }
                        }
                    }
                    
                    fclose($handle);
                } elseif ($extension === 'xlsx' || $extension === 'xls') {
                    // Parse Excel file
                    $data = Excel::toArray([], $file);
                    
                    if (empty($data) || empty($data[0])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Excel file is empty'
                        ], 400);
                    }
                    
                    $rows = $data[0];
                    // Skip first 2 header rows
                    array_shift($rows);
                    array_shift($rows);
                    
                    foreach ($rows as $row) {
                        $results['total']++;
                        
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        
                        // Expected format: S.NO, Unit No, DEWA Premise No, Status, Buyer 1 Name, Passport, Contact, Email, Buyer 2 Name, Passport, Contact, Email
                        if (count($row) < 12) {
                            $results['errors'][] = "Row {$results['total']}: Invalid format - expected 12 columns";
                            $results['skipped']++;
                            continue;
                        }

                        $unitNumber = trim($row[1]);
                        $dewaPremiseNumber = trim($row[2]);
                        $status = trim($row[3]);
                        $buyer1Name = trim($row[4]);
                        $buyer1Email = trim($row[7]);
                        $buyer2Name = isset($row[8]) ? trim($row[8]) : '';
                        $buyer2Email = isset($row[11]) ? trim($row[11]) : '';
                        
                        // Skip if no buyers (Available/Blocked status)
                        if (empty($buyer1Name) && empty($buyer1Email)) {
                            continue;
                        }

                        // Extract floor from unit number (first digit(s))
                        $floor = '';
                        if (strlen($unitNumber) >= 2) {
                            $floor = substr($unitNumber, 0, -2); // All digits except last 2
                        }

                        // Find or create unit
                        $unit = \App\Models\Unit::where('property_id', $request->property_id)
                            ->where('unit', $unitNumber)
                            ->first();

                        if (!$unit) {
                            // Create unit if it doesn't exist
                            $unit = \App\Models\Unit::create([
                                'property_id' => $request->property_id,
                                'unit' => $unitNumber,
                                'floor' => $floor,
                                'building' => $property->project_name,
                                'dewa_premise_number' => $dewaPremiseNumber,
                                'status' => 'unclaimed'
                            ]);
                        } else {
                            // Update DEWA premise number, floor, and building if unit exists
                            $unit->update([
                                'dewa_premise_number' => $dewaPremiseNumber,
                                'floor' => $floor,
                                'building' => $property->project_name
                            ]);
                        }

                        // Process Buyer 1
                        if (!empty($buyer1Name) && !empty($buyer1Email)) {
                            // Validate email format
                            if (!filter_var($buyer1Email, FILTER_VALIDATE_EMAIL)) {
                                $results['errors'][] = "Row {$results['total']}: Invalid buyer 1 email format";
                                $results['skipped']++;
                                continue;
                            }

                            // Check if user already exists
                            $existingUser = User::where('email', $buyer1Email)->first();
                            if (!$existingUser) {
                                // Generate random password
                                $password = Str::random(12);

                                // Create user
                                $user = User::create([
                                    'full_name' => $buyer1Name,
                                    'email' => $buyer1Email,
                                    'password' => Hash::make($password),
                                ]);

                                // Assign user to unit as primary
                                $user->units()->attach($unit->id, ['is_primary' => true]);

                                // Update unit status to claimed
                                $unit->update(['status' => 'claimed']);

                                $results['created']++;
                            } else {
                                // User exists, just link to unit if not already linked
                                if (!$existingUser->units()->where('unit_id', $unit->id)->exists()) {
                                    $existingUser->units()->attach($unit->id, ['is_primary' => true]);
                                    $unit->update(['status' => 'claimed']);
                                }
                            }
                        }

                        // Process Buyer 2 (co-owner)
                        if (!empty($buyer2Name) && !empty($buyer2Email)) {
                            // Validate email format
                            if (!filter_var($buyer2Email, FILTER_VALIDATE_EMAIL)) {
                                $results['errors'][] = "Row {$results['total']}: Invalid buyer 2 email format";
                                continue; // Don't skip the whole row, just buyer 2
                            }

                            // Check if user already exists
                            $existingUser2 = User::where('email', $buyer2Email)->first();
                            if (!$existingUser2) {
                                // Generate random password
                                $password = Str::random(12);

                                // Create user
                                $user2 = User::create([
                                    'full_name' => $buyer2Name,
                                    'email' => $buyer2Email,
                                    'password' => Hash::make($password),
                                ]);

                                // Assign user to unit as co-owner (not primary)
                                $user2->units()->attach($unit->id, ['is_primary' => false]);

                                $results['created']++;
                            } else {
                                // User exists, just link to unit if not already linked
                                if (!$existingUser2->units()->where('unit_id', $unit->id)->exists()) {
                                    $existingUser2->units()->attach($unit->id, ['is_primary' => false]);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unsupported file format. Please use CSV or Excel files.'
                    ], 400);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Bulk upload completed. {$results['created']} clients created, {$results['skipped']} skipped.",
                    'results' => $results
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk upload clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


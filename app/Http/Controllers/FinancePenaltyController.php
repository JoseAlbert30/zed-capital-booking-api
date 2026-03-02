<?php

namespace App\Http\Controllers;

use App\Models\FinancePenalty;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use App\Events\FinancePenaltyUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinancePenaltyController extends Controller
{
    /**
     * Get all penalties for a project
     */
    public function index(Request $request)
    {
        $project = $request->query('project');

        // Check authentication type from middleware
        $authType = $request->attributes->get('auth_type');
        
        if ($authType === 'developer') {
            // Developer is authenticated via magic link - restrict to their project
            $project = $request->attributes->get('developer_project');
        }

        $query = FinancePenalty::with(['creator:id,full_name,email', 'unit', 'attachments', 'property'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_name', $project);
        }

        $penalties = $query->get();

        return response()->json([
            'success' => true,
            'penalties' => $penalties->map(function ($penalty) {
                return [
                    'id' => $penalty->id,
                    'penaltyNumber' => $penalty->penalty_number,
                    'penaltyName' => $penalty->penalty_name,
                    'unitNumber' => $penalty->unit_number,
                    'unitId' => $penalty->unit_id,
                    'description' => $penalty->description,
                    'penaltyInitiatedBy' => $penalty->property?->penalty_initiated_by ?? 'admin',
                    'proofOfPaymentUrl' => $this->stripStorageUrl($penalty->proof_of_payment_url),
                    'proofOfPaymentName' => $penalty->proof_of_payment_name,
                    'receiptUrl' => $this->stripStorageUrl($penalty->receipt_url),
                    'receiptName' => $penalty->receipt_name,
                    'receiptSentToBuyer' => $penalty->receipt_sent_to_buyer,
                    'receiptSentToBuyerAt' => $penalty->receipt_sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'date' => $penalty->created_at->format('Y-m-d'),
                    'notificationSent' => $penalty->notification_sent,
                    'viewedByDeveloper' => $penalty->viewed_by_developer,
                    'viewedByAdmin' => $penalty->viewed_by_admin,
                    'viewedAt' => $penalty->viewed_at?->format('Y-m-d H:i:s'),
                    'notes' => $penalty->notes,
                    'attachments' => $penalty->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'fileName' => $att->file_name,
                            'fileUrl' => $this->stripStorageUrl($att->file_url),
                            'fileSize' => $att->file_size,
                            'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'timeline' => $penalty->timeline,
                ];
            }),
        ]);
    }

    /**
     * Store a new penalty
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penalty_name' => 'required|string|max:255',
            'project_name' => 'required|string',
            'unit_number' => 'required|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'proof_of_payment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // For admin-initiated penalties
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Find the unit by project and unit number
            $unit = \App\Models\Unit::whereHas('property', function ($query) use ($request) {
                $query->where('project_name', $request->project_name);
            })->where('unit', $request->unit_number)->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found in this project',
                ], 404);
            }

            // Get property to check penalty_initiated_by setting
            $property = \App\Models\Property::where('project_name', $request->project_name)->first();
            
            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            // Check authentication type and validate against setting
            $authType = $request->attributes->get('auth_type');
            $penaltyInitiatedBy = $property->penalty_initiated_by ?? 'admin';
            
            if ($penaltyInitiatedBy === 'admin' && $authType === 'developer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only administrators can create penalties for this project',
                ], 403);
            }
            
            if ($penaltyInitiatedBy === 'developer' && $authType !== 'developer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only developers can create penalties for this project',
                ], 403);
            }

            // Generate penalty number
            $lastPenalty = FinancePenalty::orderBy('id', 'desc')->first();
            if ($lastPenalty) {
                preg_match('/PEN-(\d+)/', $lastPenalty->penalty_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $penaltyNumber = 'PEN-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create the penalty record
            $penalty = FinancePenalty::create([
                'penalty_number' => $penaltyNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'penalty_name' => $request->penalty_name,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // If admin-initiated penalty and proof of payment is uploaded, save it
            if ($penaltyInitiatedBy === 'admin' && $request->hasFile('proof_of_payment')) {
                $proofDoc = $request->file('proof_of_payment');
                $fileName = 'Penalty_' . $penalty->penalty_number . '_ProofOfPayment_' . time() . '.' . $proofDoc->getClientOriginalExtension();
                $storagePath = 'finance/' . $penalty->project_name . '/' . $penalty->unit_number;
                $proofPath = $proofDoc->storeAs($storagePath, $fileName, 'public');
                
                $penalty->proof_of_payment_path = $proofPath;
                $penalty->proof_of_payment_name = $fileName;
                $penalty->proof_of_payment_uploaded_at = now();
                $penalty->save();
            }

            // Send email notification based on who initiated the penalty
            if ($request->input('send_to_developer') === 'true' || $request->input('send_notification') === 'true') {
                if ($authType === 'developer') {
                    // Developer creates penalty, notify admin
                    $this->sendPenaltyNotificationToAdmin($penalty, $property);
                    $penalty->notification_sent = true;
                    $penalty->notification_sent_at = now();
                    $penalty->save();
                } else {
                    // Admin creates penalty, notify developer
                    $this->sendPenaltyNotificationToDeveloper($penalty);
                    $penalty->notification_sent = true;
                    $penalty->notification_sent_at = now();
                    $penalty->save();
                }
            }

            $penalty->load(['creator:id,full_name,email', 'unit', 'property', 'attachments']);

            // Format penalty data for broadcast
            $penaltyData = $this->formatPenaltyData($penalty);

            // Broadcast event
            broadcast(new FinancePenaltyUpdated($penalty->project_name, 'created', $penaltyData));

            return response()->json([
                'success' => true,
                'message' => 'Penalty created successfully',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create penalty: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create penalty: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload penalty document (Developer)
     */
    public function uploadDocument(Request $request, $id)
    {
        $penalty = FinancePenalty::find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $docFile = $request->file('document');
            
            // Get developer name from middleware attributes
            $authType = $request->attributes->get('auth_type');
            $developerName = 'Developer';
            
            if ($authType === 'developer') {
                $developerName = $request->attributes->get('developer_name') ?? 'Developer';
            }

            // Store document with organized structure
            $fileName = 'Penalty_' . $penalty->penalty_number . '_' . time() . '.' . $docFile->getClientOriginalExtension();
            $storagePath = 'finance/' . $penalty->project_name . '/' . $penalty->unit_number;
            
            // Delete old document if exists
            if ($penalty->document_path && Storage::disk('public')->exists($penalty->document_path)) {
                Storage::disk('public')->delete($penalty->document_path);
            }
            
            $docPath = $docFile->storeAs($storagePath, $fileName, 'public');

            // Update penalty with document info
            $penalty->document_path = $docPath;
            $penalty->document_name = $fileName;
            $penalty->document_uploaded_at = now();
            $penalty->document_uploaded_by = $developerName;
            $penalty->save();

            // Get property to check penalty_initiated_by setting
            $property = \App\Models\Property::where('project_name', $penalty->project_name)->first();
            $penaltyInitiatedBy = $property->penalty_initiated_by ?? 'admin';

            // Send notification to the correct party
            // If admin created penalty, developer uploads → notify admin
            // If developer created penalty, admin uploads → notify developer
            if ($penaltyInitiatedBy === 'admin' && $authType === 'developer') {
                // Developer uploaded, notify admin
                $this->sendPenaltyDocumentUploadedNotificationToAdmin($penalty, $developerName);
            } elseif ($penaltyInitiatedBy === 'developer' && $authType !== 'developer') {
                // Admin uploaded, notify developer
                $this->sendPenaltyDocumentUploadedNotificationToDeveloper($penalty, $property);
            }

            $penalty->load('creator:id,full_name,email', 'unit');

            $penaltyData = [
                'id' => $penalty->id,
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'unitId' => $penalty->unit_id,
                'amount' => (float) $penalty->amount,
                'description' => $penalty->description,
                'documentUrl' => $penalty->document_url,
                'documentName' => $penalty->document_name,
                'date' => $penalty->created_at->format('Y-m-d'),
                'notificationSent' => $penalty->notification_sent,
                'viewedByDeveloper' => $penalty->viewed_by_developer,
                'viewedAt' => $penalty->viewed_at?->format('Y-m-d H:i:s'),
                'timeline' => $penalty->timeline,
            ];

            broadcast(new \App\Events\FinancePenaltyUpdated($penalty->project_name, 'uploaded', $penaltyData));

            return response()->json([
                'success' => true,
                'message' => 'Penalty document uploaded successfully',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a penalty
     */
    public function destroy($id)
    {
        $penalty = FinancePenalty::find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        try {
            $penaltyData = [
                'id' => $penalty->id,
                'penaltyNumber' => $penalty->penalty_number,
            ];
            $projectName = $penalty->project_name;
            
            $penalty->delete();

            broadcast(new \App\Events\FinancePenaltyUpdated($projectName, 'deleted', $penaltyData));

            return response()->json([
                'success' => true,
                'message' => 'Penalty deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete penalty: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend penalty notification to developer
     */
    public function resendNotification($id)
    {
        $penalty = FinancePenalty::find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        try {
            $this->sendPenaltyNotificationToDeveloper($penalty);

            return response()->json([
                'success' => true,
                'message' => 'Penalty notification resent to developer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark penalty as viewed by developer or admin (depending on who is viewing)
     */
    public function markAsViewed(Request $request, $id)
    {
        $penalty = FinancePenalty::find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        // Get property to check penalty_initiated_by setting
        $property = \App\Models\Property::where('project_name', $penalty->project_name)->first();
        $penaltyInitiatedBy = $property->penalty_initiated_by ?? 'admin';
        
        // Get authentication type from middleware
        $authType = $request->attributes->get('auth_type');
        
        try {
            // If admin created penalty, mark as viewed by developer
            // If developer created penalty, mark as viewed by admin
            if ($penaltyInitiatedBy === 'admin' && $authType === 'developer') {
                if ($penalty->viewed_by_developer) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Penalty already marked as viewed',
                    ]);
                }
                $penalty->viewed_by_developer = true;
            } elseif ($penaltyInitiatedBy === 'developer' && $authType !== 'developer') {
                if ($penalty->viewed_by_admin) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Penalty already marked as viewed',
                    ]);
                }
                $penalty->viewed_by_admin = true;
            }
            
            $penalty->viewed_at = now();
            $penalty->save();

            $penalty->load(['creator:id,full_name,email', 'unit', 'property', 'attachments']);

            // Format penalty data for broadcast
            $penaltyData = $this->formatPenaltyData($penalty);

            broadcast(new \App\Events\FinancePenaltyUpdated($penalty->project_name, 'viewed', $penaltyData));

            return response()->json([
                'success' => true,
                'message' => 'Penalty marked as viewed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark penalty as viewed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send penalty notification to developer
     */
    private function sendPenaltyNotificationToDeveloper(FinancePenalty $penalty)
    {
        try {
            // Get property/project details to find developer email
            $property = Property::where('project_name', $penalty->project_name)->first();
            
            if (!$property || !$property->developer_email) {
                Log::warning("No developer email found for project: {$penalty->project_name}");
                return;
            }

            // Generate or get existing magic link
            $magicLink = DeveloperMagicLink::where('project_name', $penalty->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $penalty->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90
                );
            }

            // Prepare email data
            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'amount' => $penalty->amount,
                'description' => $penalty->description,
                'projectName' => $penalty->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.penalty-request-notification', $emailData, function ($message) use ($property, $ccEmails, $penalty) {
                $message->to($property->developer_email)
                    ->subject("Penalty Notice: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

            // Update penalty notification status
            $penalty->notification_sent = true;
            $penalty->notification_sent_at = now();
            $penalty->save();

        } catch (\Exception $e) {
            Log::error("Failed to send penalty notification to developer: " . $e->getMessage());
        }
    }

    /**
     * Send penalty document uploaded notification to admin
     */
    private function sendPenaltyDocumentUploadedNotificationToAdmin(FinancePenalty $penalty, string $developerName)
    {
        try {
            $adminEmails = [
                'operations@zedcapital.ae',
                'docs@zedcapital.ae',
                'admin@zedcapital.ae',
                'clientsupport@zedcapital.ae',
                'wbd@zedcapital.ae',
            ];

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'amount' => $penalty->amount,
                'projectName' => $penalty->project_name,
                'developerName' => $developerName,
                'documentUrl' => url($penalty->document_url),
            ];

            Mail::send('emails.penalty-document-uploaded-notification', $emailData, function ($message) use ($adminEmails, $penalty) {
                $message->to($adminEmails)
                    ->subject("Penalty Document Uploaded: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to send penalty document uploaded notification: " . $e->getMessage());
        }
    }

    /**
     * Send penalty document uploaded notification to developer (when admin uploads)
     */
    private function sendPenaltyDocumentUploadedNotificationToDeveloper(FinancePenalty $penalty, Property $property)
    {
        try {
            if (!$property || !$property->developer_email) {
                Log::warning("No developer email found for project: {$penalty->project_name}");
                return;
            }

            // Generate or get existing magic link
            $magicLink = DeveloperMagicLink::where('project_name', $penalty->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $penalty->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90
                );
            }

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'amount' => $penalty->amount,
                'projectName' => $penalty->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'documentUrl' => url($penalty->document_url),
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.penalty-admin-uploaded-document', $emailData, function ($message) use ($property, $ccEmails, $penalty) {
                $message->to($property->developer_email)
                    ->subject("Penalty Document Uploaded: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

        } catch (\Exception $e) {
            Log::error("Failed to send penalty document uploaded notification to developer: " . $e->getMessage());
        }
    }

    /**
     * Upload proof of payment (admin only, after developer creates penalty)
     */
    public function uploadProofOfPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'proof_of_payment' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $penalty = FinancePenalty::with('property')->find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        try {
            $proofDoc = $request->file('proof_of_payment');
            $fileName = 'Penalty_' . $penalty->penalty_number . '_ProofOfPayment_' . time() . '.' . $proofDoc->getClientOriginalExtension();
            $storagePath = 'finance/' . $penalty->project_name . '/' . $penalty->unit_number;
            
            // Delete old proof if exists
            if ($penalty->proof_of_payment_path && Storage::disk('public')->exists($penalty->proof_of_payment_path)) {
                Storage::disk('public')->delete($penalty->proof_of_payment_path);
            }
            
            $proofPath = $proofDoc->storeAs($storagePath, $fileName, 'public');
            
            $penalty->proof_of_payment_path = $proofPath;
            $penalty->proof_of_payment_name = $fileName;
            $penalty->proof_of_payment_uploaded_at = now();
            $penalty->save();

            // Reload with relationships to get updated timeline
            $penalty->load(['creator:id,full_name,email', 'unit', 'attachments', 'property']);

            // Format penalty data for broadcast
            $penaltyData = $this->formatPenaltyData($penalty);

            // Broadcast event
            broadcast(new FinancePenaltyUpdated($penalty->project_name, 'proof-uploaded', $penaltyData));

            // Notify developer about proof of payment upload
            $this->sendProofUploadedNotificationToDeveloper($penalty);

            return response()->json([
                'success' => true,
                'message' => 'Proof of payment uploaded successfully',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upload proof of payment: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload proof of payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send proof uploaded notification to developer
     */
    private function sendProofUploadedNotificationToDeveloper($penalty)
    {
        try {
            $property = $penalty->property;
            
            if (!$property || !$property->developer_email) {
                return;
            }

            // Create a new magic link for developer access
            $magicLink = DeveloperMagicLink::create([
                'project_name' => $penalty->project_name,
                'email' => $property->developer_email,
                'token' => bin2hex(random_bytes(32)),
                'expires_at' => now()->addDays(7),
            ]);

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'projectName' => $penalty->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'proofUrl' => url($penalty->proof_of_payment_url),
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.penalty-proof-uploaded', $emailData, function ($message) use ($property, $ccEmails, $penalty) {
                $message->to($property->developer_email)
                    ->subject("Proof of Payment Uploaded - Action Required: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

        } catch (\Exception $e) {
            Log::error("Failed to send proof uploaded notification to developer: " . $e->getMessage());
        }
    }

    /**
     * Upload receipt (developer only, after admin uploads proof of payment)
     */
    public function uploadReceipt(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $penalty = FinancePenalty::with('property')->find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        try {
            $receiptDoc = $request->file('receipt');
            $fileName = 'Penalty_' . $penalty->penalty_number . '_Receipt_' . time() . '.' . $receiptDoc->getClientOriginalExtension();
            $storagePath = 'finance/' . $penalty->project_name . '/' . $penalty->unit_number;
            
            // Delete old receipt if exists
            if ($penalty->receipt_path && Storage::disk('public')->exists($penalty->receipt_path)) {
                Storage::disk('public')->delete($penalty->receipt_path);
            }
            
            $receiptPath = $receiptDoc->storeAs($storagePath, $fileName, 'public');
            
            $penalty->receipt_path = $receiptPath;
            $penalty->receipt_name = $fileName;
            $penalty->receipt_uploaded_at = now();
            $penalty->save();

            // Reload with relationships to get updated timeline
            $penalty->load(['creator:id,full_name,email', 'unit', 'attachments', 'property']);

            // Format penalty data for broadcast
            $penaltyData = $this->formatPenaltyData($penalty);

            // Broadcast event
            broadcast(new FinancePenaltyUpdated($penalty->project_name, 'receipt-uploaded', $penaltyData));

            // Notify admin about receipt upload
            $this->sendReceiptUploadedNotificationToAdmin($penalty);

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully. Admin has been notified.',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upload receipt: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send receipt uploaded notification to admin
     */
    private function sendReceiptUploadedNotificationToAdmin($penalty)
    {
        try {
            $property = $penalty->property;
            
            if (!$property) {
                return;
            }

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'projectName' => $penalty->project_name,
                'receiptUrl' => url($penalty->receipt_url),
            ];

            // Send to admin email or configured emails
            $adminEmail = env('ADMIN_EMAIL', 'admin@zedcapital.com');

            Mail::send('emails.penalty-receipt-uploaded', $emailData, function ($message) use ($penalty, $adminEmail) {
                $message->to($adminEmail)
                    ->subject("Penalty Receipt Uploaded: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to send receipt uploaded notification to admin: " . $e->getMessage());
        }
    }

    /**
     * Send receipt to buyer via finance email
     */
    public function sendReceiptToBuyer(Request $request, $id)
    {
        $penalty = FinancePenalty::with(['unit.primaryFinanceEmail', 'property'])->find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        if (!$penalty->receipt_path) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt has not been uploaded yet',
            ], 400);
        }

        try {
            // Get buyer email from unit finance emails
            $buyerEmail = null;
            $buyerName = 'Buyer';

            if ($penalty->unit && $penalty->unit->primaryFinanceEmail) {
                $financeEmail = $penalty->unit->primaryFinanceEmail;
                $buyerEmail = $financeEmail->email;
                $buyerName = $financeEmail->recipient_name ?? 'Buyer';
            }

            if (!$buyerEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No buyer email found for this unit',
                ], 400);
            }

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'projectName' => $penalty->project_name,
                'buyerName' => $buyerName,
                'receiptUrl' => url($penalty->receipt_url),
            ];

            Mail::send('emails.penalty-receipt-to-buyer', $emailData, function ($message) use ($buyerEmail, $penalty) {
                $message->to($buyerEmail)
                    ->subject("Penalty Payment Receipt: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
            });

            $penalty->receipt_sent_to_buyer = true;
            $penalty->receipt_sent_to_buyer_at = now();
            $penalty->receipt_sent_to_buyer_email = $buyerEmail;
            $penalty->save();

            // Reload with relationships to get updated timeline
            $penalty->load(['creator:id,full_name,email', 'unit', 'attachments', 'property']);

            // Format penalty data for broadcast
            $penaltyData = $this->formatPenaltyData($penalty);

            // Broadcast event
            broadcast(new FinancePenaltyUpdated($penalty->project_name, 'receipt-sent-to-buyer', $penaltyData));

            return response()->json([
                'success' => true,
                'message' => 'Receipt sent to buyer successfully',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send receipt to buyer: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send receipt to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send penalty document to buyer via finance email
     */
    public function sendToBuyer(Request $request, $id)
    {
        $penalty = FinancePenalty::with('unit.primaryFinanceEmail')->find($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty not found',
            ], 404);
        }

        if (!$penalty->document_path) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty document has not been uploaded yet',
            ], 400);
        }

        try {
            // Get buyer email from unit finance emails
            $buyerEmail = null;
            $buyerName = 'Buyer';

            if ($penalty->unit && $penalty->unit->primaryFinanceEmail) {
                $financeEmail = $penalty->unit->primaryFinanceEmail;
                $buyerEmail = $financeEmail->email;
                $buyerName = $financeEmail->recipient_name ?? 'Buyer';
            }

            if (!$buyerEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No finance email found for this unit. Please add a finance email first.',
                ], 400);
            }

            // Prepare email data
            $emailData = [
                'buyerName' => $buyerName,
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'projectName' => $penalty->project_name,
                'documentUrl' => url('api/storage/' . $penalty->document_path),
            ];

            // Send email
            Mail::send('emails.penalty-sent-to-buyer', $emailData, function ($message) use ($buyerEmail, $penalty) {
                $message->to($buyerEmail)
                    ->subject("Penalty Notice - {$penalty->penalty_name}");
            });

            // Update penalty
            $penalty->sent_to_buyer = true;
            $penalty->sent_to_buyer_at = now();
            $penalty->sent_to_buyer_email = $buyerEmail;
            $penalty->save();

            $penaltyData = [
                'id' => $penalty->id,
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'unitId' => $penalty->unit_id,
                'documentUrl' => $penalty->document_url,
                'documentName' => $penalty->document_name,
                'date' => $penalty->created_at->format('Y-m-d'),
                'sentToBuyer' => $penalty->sent_to_buyer,
                'sentToBuyerAt' => $penalty->sent_to_buyer_at?->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Penalty sent to buyer successfully',
                'penalty' => $penaltyData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send penalty to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload attachment to penalty
     */
    public function uploadAttachment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'attachment' => 'required|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $penalty = FinancePenalty::findOrFail($id);
            $file = $request->file('attachment');
            
            // Generate unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('finance/penalty/attachments', $filename, 'public');
            
            // Create attachment record
            $attachment = $penalty->attachments()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully',
                'attachment' => [
                    'id' => $attachment->id,
                    'fileName' => $attachment->file_name,
                    'fileUrl' => $attachment->file_url,
                    'fileSize' => $attachment->file_size,
                    'uploadedAt' => $attachment->created_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete attachment from penalty
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $penalty = FinancePenalty::findOrFail($id);
            $attachment = $penalty->attachments()->findOrFail($attachmentId);
            
            // Delete file from storage
            Storage::disk('public')->delete($attachment->file_path);
            
            // Delete database record
            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send penalty notification to admin (when developer creates penalty)
     */
    private function sendPenaltyNotificationToAdmin(FinancePenalty $penalty, Property $property)
    {
        try {
            $adminEmails = [
                'operations@zedcapital.ae',
                'docs@zedcapital.ae',
                'admin@zedcapital.ae',
                'clientsupport@zedcapital.ae',
                'wbd@zedcapital.ae',
            ];

            $emailData = [
                'penaltyNumber' => $penalty->penalty_number,
                'penaltyName' => $penalty->penalty_name,
                'unitNumber' => $penalty->unit_number,
                'amount' => $penalty->amount,
                'description' => $penalty->description,
                'notes' => $penalty->notes,
                'projectName' => $penalty->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'dashboardLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/admin/finance?project=' . urlencode($penalty->project_name),
            ];

            Mail::send('emails.penalty-to-admin-notification', $emailData, function ($message) use ($adminEmails, $penalty) {
                $message->to($adminEmails)
                    ->subject("New Penalty Submitted by Developer: {$penalty->penalty_name} - Unit {$penalty->unit_number}");
            });

            // Update penalty notification status
            $penalty->notification_sent = true;
            $penalty->notification_sent_at = now();
            $penalty->save();

        } catch (\Exception $e) {
            Log::error("Failed to send penalty notification to admin: " . $e->getMessage());
        }
    }

    /**
     * Format penalty data for broadcast/response
     */
    private function formatPenaltyData($penalty)
    {
        // Ensure relationships are loaded
        $penalty->loadMissing(['property', 'attachments']);

        return [
            'id' => $penalty->id,
            'penaltyNumber' => $penalty->penalty_number,
            'penaltyName' => $penalty->penalty_name,
            'unitNumber' => $penalty->unit_number,
            'unitId' => $penalty->unit_id,
            'description' => $penalty->description,
            'penaltyInitiatedBy' => $penalty->property?->penalty_initiated_by ?? 'admin',
            'proofOfPaymentUrl' => $this->stripStorageUrl($penalty->proof_of_payment_url),
            'proofOfPaymentName' => $penalty->proof_of_payment_name,
            'receiptUrl' => $this->stripStorageUrl($penalty->receipt_url),
            'receiptName' => $penalty->receipt_name,
            'receiptSentToBuyer' => $penalty->receipt_sent_to_buyer,
            'receiptSentToBuyerAt' => $penalty->receipt_sent_to_buyer_at?->format('Y-m-d H:i:s'),
            'date' => $penalty->created_at->format('Y-m-d'),
            'notificationSent' => $penalty->notification_sent,
            'viewedByDeveloper' => $penalty->viewed_by_developer,
            'viewedByAdmin' => $penalty->viewed_by_admin,
            'viewedAt' => $penalty->viewed_at?->format('Y-m-d H:i:s'),
            'notes' => $penalty->notes,
            'attachments' => $penalty->attachments->map(function ($att) {
                return [
                    'id' => $att->id,
                    'fileName' => $att->file_name,
                    'fileUrl' => $this->stripStorageUrl($att->file_url),
                    'fileSize' => $att->file_size,
                    'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'timeline' => $penalty->timeline,
        ];
    }

    /**
     * Strip the storage URL prefix to return only the relative path
     */
    private function stripStorageUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        // Remove http://localhost/storage/ or any other domain/storage/ prefix
        $url = preg_replace('#^https?://[^/]+/storage/#', '', $url);
        
        return $url;
    }
}

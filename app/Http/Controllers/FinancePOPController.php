<?php

namespace App\Http\Controllers;

use App\Models\FinancePOP;
use App\Models\DeveloperMagicLink;
use App\Models\Property;
use App\Models\DevUser;
use App\Models\FinanceAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\POPNotificationMail;
use App\Mail\POPDeveloperNotification;

class FinancePOPController extends Controller
{
    /**
     * Get all POPs for a project
     */
    public function index(Request $request)
    {
        $project = $request->query('project');

        // Check authentication type from middleware
        $authType = $request->attributes->get('auth_type');
        $user = $request->user();
        
        if ($authType === 'developer') {
            // Validate developer has access to the requested project
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project parameter is required',
                ], 400);
            }
            
            if (!FinanceAccess::hasAccess($user->id, $project)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this project',
                ], 403);
            }
        }

        $query = FinancePOP::with(['creator:id,full_name,email', 'unit'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_name', $project);
        }

        $pops = $query->get();

        return response()->json([
            'success' => true,
            'pops' => $pops->map(function ($pop) {
                return [
                    'id' => $pop->id,
                    'popNumber' => $pop->pop_number,
                    'unitNumber' => $pop->unit_number,
                    'unitId' => $pop->unit_id,
                    'attachmentUrl' => $pop->attachment_url,
                    'attachmentName' => $pop->attachment_name,
                    'receiptUrl' => $pop->receipt_url,
                    'receiptName' => $pop->receipt_name,
                    'buyerEmail' => $pop->buyer_email,
                    'date' => $pop->created_at->format('Y-m-d'),
                    'notificationSent' => $pop->notification_sent,
                    'viewedByDeveloper' => $pop->viewed_by_developer,
                    'viewedAt' => $pop->viewed_at?->format('Y-m-d H:i:s'),
                    'receiptSentToBuyer' => $pop->receipt_sent_to_buyer,
                    'receiptSentToBuyerAt' => $pop->receipt_sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'notes' => $pop->notes,
                    'timeline' => $pop->timeline,
                    'createdBy' => $pop->creator ? [
                        'name' => $pop->creator->full_name,
                        'email' => $pop->creator->email,
                    ] : null,
                    'unit' => $pop->unit ? [
                        'id' => $pop->unit->id,
                        'unit_number' => $pop->unit->unit_number,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Store a new POP
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pop_number' => 'nullable|string|unique:finance_pops,pop_number',
            'project_name' => 'required|string',
            'unit_number' => 'required|string',
            'buyer_email' => 'nullable|email',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'notes' => 'nullable|string',
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

            // Generate POP number if not provided
            $popNumber = $request->pop_number;
            if (!$popNumber) {
                // Get project prefix from property
                $property = Property::where('project_name', $request->project_name)->first();
                $prefix = $property && $property->code_prefix ? $property->code_prefix : 'PROJ';

                // Use the highest existing number for this project/prefix
                $lastPop = FinancePOP::where('project_name', $request->project_name)
                    ->where('pop_number', 'like', $prefix . '-POP-%')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastPop) {
                    preg_match('/' . preg_quote($prefix, '/') . '-POP-(\d+)/', $lastPop->pop_number, $matches);
                    $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $popNumber = $prefix . '-POP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            // Store the attachment with new structure: finance/{project_name}/{unit_no}/
            $file = $request->file('attachment');
            $fileName = 'POP_' . $popNumber . '_' . time() . '.' . $file->getClientOriginalExtension();
            $storagePath = 'finance/' . $request->project_name . '/' . $request->unit_number;
            $path = $file->storeAs($storagePath, $fileName, 'public');

            // Create the POP record
            $pop = FinancePOP::create([
                'pop_number' => $popNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'buyer_email' => $request->buyer_email,
                'attachment_path' => $path,
                'attachment_name' => $fileName,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Handle sending email to developer if requested
            if ($request->input('send_to_developer') === 'true') {
                $this->sendToDeveloper($pop);
            }

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'created', $popData));

            // Broadcast pending counts update to developers with access to this project
            $this->broadcastPendingCountsForProject($request->project_name);

            return response()->json([
                'success' => true,
                'message' => $request->input('send_to_developer') === 'true' 
                    ? 'POP created and notification sent to developer' 
                    : 'POP created successfully',
                'pop' => $popData,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create POP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a POP
     */
    public function update(Request $request, $id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit_number' => 'required|string',
            'buyer_email' => 'nullable|email',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
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
            $unit = \App\Models\Unit::whereHas('property', function ($query) use ($pop) {
                $query->where('project_name', $pop->project_name);
            })->where('unit', $request->unit_number)->first();

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit not found in this project',
                ], 404);
            }

            $pop->unit_id = $unit->id;
            $pop->unit_number = $request->unit_number;
            $pop->buyer_email = $request->buyer_email;

            // Update attachment if provided
            if ($request->hasFile('attachment')) {
                // Delete old file
                if ($pop->attachment_path && Storage::disk('public')->exists($pop->attachment_path)) {
                    Storage::disk('public')->delete($pop->attachment_path);
                }

                // Store new file with new structure: finance/{project_name}/{unit_no}/
                $file = $request->file('attachment');
                $fileName = 'POP_' . $pop->pop_number . '_' . time() . '.' . $file->getClientOriginalExtension();
                $storagePath = 'finance/' . $pop->project_name . '/' . $request->unit_number;
                $path = $file->storeAs($storagePath, $fileName, 'public');

                $pop->attachment_path = $path;
                $pop->attachment_name = $fileName;
            }

            $pop->save();
            $pop->load('creator:id,full_name,email', 'unit');

            return response()->json([
                'success' => true,
                'message' => 'POP updated successfully',
                'pop' => [
                    'id' => $pop->id,
                    'popNumber' => $pop->pop_number,
                    'unitNumber' => $pop->unit_number,
                    'unitId' => $pop->unit_id,
                    'buyerEmail' => $pop->buyer_email,
                    'attachmentUrl' => $pop->attachment_url,
                    'attachmentName' => $pop->attachment_name,
                    'date' => $pop->created_at->format('Y-m-d'),
                    'notificationSent' => $pop->notification_sent,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update POP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a POP
     */
    public function destroy($id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        try {
            $popData = [
                'id' => $pop->id,
                'popNumber' => $pop->pop_number,
            ];
            $projectName = $pop->project_name;
            
            $pop->delete();

            broadcast(new \App\Events\FinancePOPUpdated($projectName, 'deleted', $popData));

            return response()->json([
                'success' => true,
                'message' => 'POP deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete POP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notification to developer
     */
    public function sendNotification($id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        try {
            // Use the sendToDeveloper method
            $this->sendToDeveloper($pop);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully to developer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get project settings
     */
    public function getProjectSettings(Request $request, $projectName)
    {
        try {
            // Check authentication type from middleware
            $authType = $request->attributes->get('auth_type');
            $user = $request->user();
            
            if ($authType === 'developer') {
                // Validate developer has access to the requested project
                if (!FinanceAccess::hasAccess($user->id, $projectName)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this project',
                    ], 403);
                }
            }
            
            $property = Property::where('project_name', $projectName)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'developer_email' => $property->developer_email,
                'developer_name' => $property->developer_name,
                'cc_emails' => $property->cc_emails,
                'penalty_initiated_by' => $property->penalty_initiated_by ?? 'admin',
                'code_prefix' => $property->code_prefix,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update project settings
     */
    public function updateProjectSettings(Request $request, $projectName)
    {
        $validator = Validator::make($request->all(), [
            'developer_email' => 'nullable|email',
            'developer_name' => 'nullable|string',
            'cc_emails' => 'nullable|string',
            'penalty_initiated_by' => 'nullable|in:admin,developer',
            'code_prefix' => 'nullable|string|min:4|max:10|regex:/^[A-Z0-9]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $property = Property::where('project_name', $projectName)->first();

            if (!$property) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found',
                ], 404);
            }

            $property->developer_email = $request->input('developer_email');
            $property->developer_name = $request->input('developer_name');
            $property->cc_emails = $request->input('cc_emails');
            
            if ($request->has('penalty_initiated_by')) {
                $property->penalty_initiated_by = $request->input('penalty_initiated_by');
            }
            
            if ($request->has('code_prefix')) {
                $property->code_prefix = $request->input('code_prefix');
            }
            
            $property->save();

            // Auto-grant finance access if developer emails already have accounts
            $autoGrantedEmails = [];
            
            // Check and grant for developer_email
            if ($property->developer_email) {
                $devUser = DevUser::where('email', $property->developer_email)->first();
                if ($devUser) {
                    FinanceAccess::grantAccess($devUser->id, $property->project_name);
                    $autoGrantedEmails[] = $property->developer_email;
                }
            }
            
            // Check and grant for cc_emails
            if ($property->cc_emails) {
                $ccEmailsArray = array_map('trim', explode(',', $property->cc_emails));
                foreach ($ccEmailsArray as $ccEmail) {
                    if (!empty($ccEmail)) {
                        $devUser = DevUser::where('email', $ccEmail)->first();
                        if ($devUser) {
                            FinanceAccess::grantAccess($devUser->id, $property->project_name);
                            $autoGrantedEmails[] = $ccEmail;
                        }
                    }
                }
            }

            $message = 'Settings updated successfully';
            if (count($autoGrantedEmails) > 0) {
                $message .= '. Finance access automatically granted to: ' . implode(', ', $autoGrantedEmails);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'auto_granted_count' => count($autoGrantedEmails),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload receipt for a POP (Developer action)
     */
    public function uploadReceipt(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pop = FinancePOP::findOrFail($id);

            // Store the receipt file with new structure: finance/{project_name}/{unit_no}/
            $receiptFile = $request->file('receipt');
            $receiptFileName = 'Receipt_' . $pop->pop_number . '_' . time() . '.' . $receiptFile->getClientOriginalExtension();
            $storagePath = 'finance/' . $pop->project_name . '/' . $pop->unit_number;
            $receiptPath = $receiptFile->storeAs($storagePath, $receiptFileName, 'public');

            // Get developer name from authenticated user
            $authType = $request->attributes->get('auth_type');
            $uploaderName = ($authType === 'developer' && $request->user()) ? $request->user()->name : 'Developer';

            // Update POP with receipt info
            $pop->receipt_path = $receiptPath;
            $pop->receipt_name = $receiptFileName;
            $pop->receipt_uploaded_at = now();
            $pop->receipt_uploaded_by = $uploaderName;
            $pop->save();

            // Send email notification to admin
            $this->sendReceiptUploadedNotificationToAdmin($pop, $uploaderName);

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'receipt-uploaded', $popData));

            // Broadcast pending counts update to developers with access
            if ($authType === 'developer') {
                $this->broadcastPendingCountsForProject($pop->project_name);
            }

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'pop' => $popData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send receipt uploaded notification to admin
     */
    private function sendReceiptUploadedNotificationToAdmin(FinancePOP $pop, string $developerName)
    {
        try {
            $adminEmails = [
                'wbd@zedcapital.ae'
            ];

            $emailData = [
                'subject' => "Receipt Uploaded for POP {$pop->pop_number} - Unit {$pop->unit_number}",
                'greeting' => 'Receipt Upload Notification',
                'transactionType' => 'POP Receipt',
                'messageBody' => "A receipt has been uploaded by {$developerName} for the following POP.",
                'details' => [
                    'POP Number' => $pop->pop_number,
                    'Unit Number' => $pop->unit_number,
                    'Project' => $pop->project_name,
                    'Uploaded By' => $developerName,
                ],
                'buttonUrl' => url($pop->receipt_url),
                'buttonText' => 'View Receipt',
            ];

            Mail::mailer('finance')->send('emails.finance-to-admin', $emailData, function ($message) use ($adminEmails, $pop) {
                $message->to($adminEmails)
                    ->subject("Receipt Uploaded for POP {$pop->pop_number} - Unit {$pop->unit_number}");
            });

        } catch (\Exception $e) {
            \Log::error("Failed to send receipt uploaded notification: " . $e->getMessage());
        }
    }

    /**
     * Request SOA from developer
     */
    public function requestSOA($id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        if (!$pop->receipt_path) {
            return response()->json([
                'success' => false,
                'message' => 'POP must have a receipt before requesting SOA',
            ], 400);
        }

        try {
            // Update POP status
            $pop->soa_requested = true;
            $pop->soa_requested_at = now();
            $pop->save();

            // Send email to developer
            $this->sendSOARequestToDeveloper($pop);

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'soa-requested', $popData));

            return response()->json([
                'success' => true,
                'message' => 'SOA request sent to developer',
                'pop' => $popData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request SOA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend SOA request to developer
     */
    public function resendSOARequest($id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        if (!$pop->soa_requested) {
            return response()->json([
                'success' => false,
                'message' => 'SOA has not been requested yet',
            ], 400);
        }

        try {
            // Resend email to developer
            $this->sendSOARequestToDeveloper($pop);

            return response()->json([
                'success' => true,
                'message' => 'SOA request resent to developer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend SOA request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SOA request email to developer
     */
    private function sendSOARequestToDeveloper(FinancePOP $pop)
    {
        try {
            // Get property/project details to find developer email
            $property = Property::where('project_name', $pop->project_name)->first();
            
            if (!$property || !$property->developer_email) {
                \Log::warning("No developer email found for project: {$pop->project_name}");
                return;
            }

            // Generate or get existing magic link for this developer/project
            $magicLink = DeveloperMagicLink::where('project_name', $pop->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $pop->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90 // 90 days validity
                );
            }

            // Prepare email data
            $emailData = [
                'subject' => "SOA Request for POP {$pop->pop_number} - Unit {$pop->unit_number}",
                'transactionType' => 'POP - SOA Request',
                'developerName' => $property->developer_name ?? 'Developer',
                'messageBody' => 'We are requesting a Statement of Account (SOA) for the following POP. Please upload the SOA documents at your earliest convenience.',
                'details' => [
                    'POP Number' => $pop->pop_number,
                    'Unit Number' => $pop->unit_number,
                    'Project' => $pop->project_name,
                    'Receipt Number' => $pop->receipt_name,
                    'Notes' => $pop->notes,
                ],
                'magicLink' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
                'buttonUrl' => url($pop->attachment_url),
                'buttonText' => 'View POP Document',
                'additionalInfo' => [
                    'title' => 'Action Required',
                    'message' => 'Please log in to the developer portal using the button above to upload the SOA documents.'
                ],
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::mailer('finance')->send('emails.finance-to-developer', $emailData, function ($message) use ($property, $ccEmails, $pop) {
                $message->to($property->developer_email)
                    ->subject("SOA Request for POP {$pop->pop_number} - Unit {$pop->unit_number}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

        } catch (\Exception $e) {
            \Log::error("Failed to send SOA request to developer: " . $e->getMessage());
        }
    }

    /**
     * Upload SOA documents (Developer)
     */
    public function uploadSOA(Request $request, $id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        if (!$pop->soa_requested) {
            return response()->json([
                'success' => false,
                'message' => 'SOA has not been requested for this POP',
            ], 400);
        }

        $validator = \Validator::make($request->all(), [
            'soa_docs' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $soaFile = $request->file('soa_docs');
            
            // Get developer name from authenticated user
            $authType = $request->attributes->get('auth_type');
            $uploaderName = $authType === 'developer' && $request->user() ? $request->user()->name : 'Developer';

            // Store SOA with organized structure: finance/{project_name}/{unit_no}/
            $fileName = 'SOA_' . $pop->pop_number . '_' . time() . '.' . $soaFile->getClientOriginalExtension();
            $storagePath = 'finance/' . $pop->project_name . '/' . $pop->unit_number;
            
            // Delete old SOA file if exists
            if ($pop->soa_docs_path && Storage::disk('public')->exists($pop->soa_docs_path)) {
                Storage::disk('public')->delete($pop->soa_docs_path);
            }
            
            $soaPath = $soaFile->storeAs($storagePath, $fileName, 'public');

            // Update POP with SOA info
            $pop->soa_docs_path = $soaPath;
            $pop->soa_docs_name = $fileName;
            $pop->soa_docs_uploaded_at = now();
            $pop->soa_uploaded_by = $uploaderName;
            $pop->save();

            // Send notification to admin team
            $this->sendSOAUploadedNotificationToAdmin($pop, $uploaderName);

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'soa-uploaded', $popData));

            return response()->json([
                'success' => true,
                'message' => 'SOA uploaded successfully',
                'pop' => [
                    'id' => $pop->id,
                    'popNumber' => $pop->pop_number,
                    'unitNumber' => $pop->unit_number,
                    'unitId' => $pop->unit_id,
                    'amount' => (float) $pop->amount,
                    'attachmentUrl' => $pop->attachment_url,
                    'attachmentName' => $pop->attachment_name,
                    'receiptUrl' => $pop->receipt_url,
                    'receiptName' => $pop->receipt_name,
                    'soaDocsUrl' => $pop->soa_docs_url,
                    'soaDocsName' => $pop->soa_docs_name,
                    'date' => $pop->created_at->format('Y-m-d'),
                    'notificationSent' => $pop->notification_sent,
                    'soaRequested' => $pop->soa_requested,
                    'timeline' => $pop->timeline,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload SOA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SOA uploaded notification to admin team
     */
    private function sendSOAUploadedNotificationToAdmin(FinancePOP $pop, string $developerName)
    {
        try {
            $adminEmails = [
                'wbd@zedcapital.ae'
            ];

            $emailData = [
                'subject' => "SOA Uploaded for POP {$pop->pop_number} - Unit {$pop->unit_number}",
                'greeting' => 'SOA Upload Notification',
                'transactionType' => 'POP - SOA Upload',
                'messageBody' => "A Statement of Account has been uploaded by {$developerName} for the following POP.",
                'details' => [
                    'POP Number' => $pop->pop_number,
                    'Unit Number' => $pop->unit_number,
                    'Project' => $pop->project_name,
                    'Uploaded By' => $developerName,
                ],
                'buttonUrl' => url($pop->soa_docs_url),
                'buttonText' => 'View SOA Documents',
            ];

            Mail::mailer('finance')->send('emails.finance-to-admin', $emailData, function ($message) use ($adminEmails, $pop) {
                $message->to($adminEmails)
                    ->subject("SOA Uploaded for POP {$pop->pop_number} - Unit {$pop->unit_number}");
            });

        } catch (\Exception $e) {
            \Log::error("Failed to send SOA uploaded notification: " . $e->getMessage());
        }
    }

    /**
     * Format POP data for JSON response
     */
    private function formatPOPResponse(FinancePOP $pop)
    {
        return [
            'id' => $pop->id,
            'popNumber' => $pop->pop_number,
            'unitNumber' => $pop->unit_number,
            'unitId' => $pop->unit_id,
            'attachmentUrl' => $pop->attachment_url,
            'attachmentName' => $pop->attachment_name,
            'receiptUrl' => $pop->receipt_url,
            'receiptName' => $pop->receipt_name,
            'buyerEmail' => $pop->buyer_email,
            'date' => $pop->created_at->format('Y-m-d'),
            'notificationSent' => $pop->notification_sent,
            'viewedByDeveloper' => $pop->viewed_by_developer,
            'viewedAt' => $pop->viewed_at?->format('Y-m-d H:i:s'),
            'timeline' => $pop->timeline,
        ];
    }

    /**
     * Mark POP as viewed by developer
     */
    public function markAsViewed($id)
    {
        $pop = FinancePOP::find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        if ($pop->viewed_by_developer) {
            return response()->json([
                'success' => true,
                'message' => 'POP already marked as viewed',
            ]);
        }

        try {
            $pop->viewed_by_developer = true;
            $pop->viewed_at = now();
            $pop->save();

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'viewed', $popData));

            return response()->json([
                'success' => true,
                'message' => 'POP marked as viewed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark POP as viewed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send POP notification to developer with magic link
     */
    private function sendToDeveloper(FinancePOP $pop)
    {
        try {
            // Get property/project details to find developer email
            $property = Property::where('project_name', $pop->project_name)->first();
            
            if (!$property || !$property->developer_email) {
                \Log::warning("No developer email found for project: {$pop->project_name}");
                return;
            }

            // Generate or get existing magic link for this developer/project
            $magicLink = DeveloperMagicLink::where('project_name', $pop->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $pop->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90 // 90 days validity
                );
            }

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            // Prepare email data
            $emailData = [
                'subject' => 'New Payment - Proof of Payment Received - ' . $pop->pop_number,
                'transactionType' => 'POP Document',
                'developerName' => $property->developer_name ?? 'Developer',
                'messageBody' => 'A new Proof of Payment has been submitted and requires your attention. Please log in to the developer portal to review and process this document.',
                'details' => [
                    'POP Number' => $pop->pop_number,
                    'Unit Number' => $pop->unit_number,
                    'Project' => $pop->project_name,
                    'Notes' => $pop->notes,
                ],
                'magicLink' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
                'buttonUrl' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
                'buttonText' => 'Access Developer Portal',
            ];

            // Send email
            Mail::mailer('finance')->send('emails.finance-to-developer', $emailData, function ($message) use ($property, $ccEmails, $pop) {
                $message->to($property->developer_email)
                    ->subject('New Payment - Proof of Payment Received - ' . $pop->pop_number);
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

            // Update POP notification status
            $pop->notification_sent = true;
            $pop->notification_sent_at = now();
            $pop->save();

        } catch (\Exception $e) {
            \Log::error("Failed to send POP to developer: " . $e->getMessage());
            // Don't throw - just log the error so POP creation still succeeds
        }
    }

    /**
     * Send receipt to buyer via finance email
     */
    public function sendReceiptToBuyer(Request $request, $id)
    {
        $pop = FinancePOP::with('unit.primaryFinanceEmail')->find($id);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        if (!$pop->receipt_path) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt has not been uploaded yet',
            ], 400);
        }

        try {
            // Get buyer email and name from request or unit finance emails
            $buyerEmail = $request->input('to_email');
            $buyerName = $request->input('recipient_name');

            // If not provided in request, get from unit
            if (!$buyerEmail && $pop->unit && $pop->unit->primaryFinanceEmail) {
                $financeEmail = $pop->unit->primaryFinanceEmail;
                $buyerEmail = $financeEmail->email;
                $buyerName = $financeEmail->recipient_name ?? 'Buyer';
            }

            if (!$buyerEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No finance email found for this unit. Please add a finance email first.',
                ], 400);
            }

            // Save/update finance email if provided in request
            if ($request->input('to_email') && $pop->unit_id) {
                \App\Models\FinanceEmail::updateOrCreate(
                    [
                        'unit_id' => $pop->unit_id,
                        'type' => 'buyer',
                        'is_primary' => true,
                    ],
                    [
                        'email' => $request->input('to_email'),
                        'recipient_name' => $request->input('recipient_name', 'Buyer'),
                    ]
                );
            }

            // Prepare email data
            $emailData = [
                'subject' => "Payment Receipt - Unit {$pop->unit_number}",
                'transactionType' => 'POP Receipt',
                'buyerName' => $buyerName,
                'messageBody' => 'Thank you for your payment. Please find attached your payment receipt for your reference.',
                'details' => [
                    'POP Number' => $pop->pop_number,
                    'Unit Number' => $pop->unit_number,
                    'Project' => $pop->project_name,
                ],
                'buttonUrl' => url('api/storage/' . $pop->receipt_path),
                'buttonText' => 'View Receipt',
            ];

            // Send email with receipt attachment
            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($buyerEmail, $pop) {
                $message->to($buyerEmail)
                    ->subject("Payment Receipt - Unit {$pop->unit_number}");
                
                // Attach receipt file if it exists
                if ($pop->receipt_path && \Storage::disk('public')->exists($pop->receipt_path)) {
                    $filePath = storage_path('app/public/' . $pop->receipt_path);
                    $message->attach($filePath, [
                        'as' => $pop->receipt_name ?? 'Receipt.pdf',
                        'mime' => mime_content_type($filePath)
                    ]);
                }
            });

            // Update POP
            $pop->receipt_sent_to_buyer = true;
            $pop->receipt_sent_to_buyer_at = now();
            $pop->receipt_sent_to_buyer_email = $buyerEmail;
            $pop->save();

            $popData = [
                'id' => $pop->id,
                'popNumber' => $pop->pop_number,
                'unitNumber' => $pop->unit_number,
                'unitId' => $pop->unit_id,
                'receiptUrl' => $pop->receipt_url,
                'receiptName' => $pop->receipt_name,
                'date' => $pop->created_at->format('Y-m-d'),
                'receiptSentToBuyer' => $pop->receipt_sent_to_buyer,
                'receiptSentToBuyerAt' => $pop->receipt_sent_to_buyer_at?->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Receipt sent to buyer successfully',
                'pop' => $popData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send receipt to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Broadcast pending counts to all developers with access to a project
     */
    private function broadcastPendingCountsForProject(string $projectName)
    {
        try {
            // Get all dev users with access to this project
            $devUserIds = FinanceAccess::where('project_name', $projectName)
                ->where('is_active', true)
                ->pluck('dev_user_id');

            $devUsers = DevUser::whereIn('id', $devUserIds)->get();

            foreach ($devUsers as $devUser) {
                \App\Http\Controllers\DeveloperPortalController::broadcastPendingCounts($devUser->email);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast pending counts: ' . $e->getMessage());
        }
    }
}

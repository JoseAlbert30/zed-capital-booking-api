<?php

namespace App\Http\Controllers;

use App\Models\FinancePOP;
use App\Models\DeveloperMagicLink;
use App\Models\Property;
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
        
        if ($authType === 'developer') {
            // Developer is authenticated via magic link - restrict to their project
            $project = $request->attributes->get('developer_project');
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
                    'viewedByDeveloper' => $pop->viewed_by_developer,
                    'viewedAt' => $pop->viewed_at?->format('Y-m-d H:i:s'),
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
            'amount' => 'required|numeric|min:0',
            'attachment' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
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
                // Get the last POP number
                $lastPop = FinancePOP::orderBy('id', 'desc')->first();
                if ($lastPop) {
                    preg_match('/POP-(\d+)/', $lastPop->pop_number, $matches);
                    $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }
                $popNumber = 'POP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
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
                'amount' => $request->amount,
                'attachment_path' => $path,
                'attachment_name' => $fileName,
                'created_by' => auth()->id(),
            ]);

            // Handle sending email to developer if requested
            if ($request->input('send_to_developer') === 'true') {
                $this->sendToDeveloper($pop);
            }

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'created', $popData));

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
            'amount' => 'required|numeric|min:0',
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
            $pop->amount = $request->amount;

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
                    'amount' => (float) $pop->amount,
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
            $property->save();

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
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

            // Get developer info from middleware or request
            $authType = $request->attributes->get('auth_type');
            $developerName = 'Developer';
            
            if ($authType === 'developer') {
                // Get developer name from magic link
                $magicLink = $request->attributes->get('developer_magic_link');
                if ($magicLink && $magicLink->developer_name) {
                    $developerName = $magicLink->developer_name;
                }
            }

            // Update POP with receipt info
            $pop->receipt_path = $receiptPath;
            $pop->receipt_name = $receiptFileName;
            $pop->receipt_uploaded_at = now();
            $pop->receipt_uploaded_by = $developerName;
            $pop->save();

            // Send email notification to admin
            $this->sendReceiptUploadedNotificationToAdmin($pop, $developerName);

            $pop->load('creator:id,full_name,email', 'unit');

            $popData = $this->formatPOPResponse($pop);

            broadcast(new \App\Events\FinancePOPUpdated($pop->project_name, 'receipt-uploaded', $popData));

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
                'operations@zedcapital.ae',
                'docs@zedcapital.ae',
                'admin@zedcapital.ae',
                'clientsupport@zedcapital.ae',
                'wbd@zedcapital.ae'
            ];

            $emailData = [
                'popNumber' => $pop->pop_number,
                'unitNumber' => $pop->unit_number,
                'amount' => $pop->amount,
                'projectName' => $pop->project_name,
                'developerName' => $developerName,
                'receiptUrl' => url($pop->receipt_url),
            ];

            Mail::send('emails.receipt-uploaded-notification', $emailData, function ($message) use ($adminEmails, $pop) {
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
                'popNumber' => $pop->pop_number,
                'unitNumber' => $pop->unit_number,
                'amount' => $pop->amount,
                'projectName' => $pop->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'receiptNumber' => $pop->receipt_name,
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
                'popUrl' => url($pop->attachment_url),
                'receiptUrl' => url($pop->receipt_url),
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.soa-request-notification', $emailData, function ($message) use ($property, $ccEmails, $pop) {
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
            
            // Get developer name from middleware attributes (if developer) or default
            $authType = $request->attributes->get('auth_type');
            $developerName = 'Developer';
            
            if ($authType === 'developer') {
                $developerName = $request->attributes->get('developer_name') ?? 'Developer';
            }

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
            $pop->soa_uploaded_by = $developerName;
            $pop->save();

            // Send notification to admin team
            $this->sendSOAUploadedNotificationToAdmin($pop, $developerName);

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
                'operations@zedcapital.ae',
                'docs@zedcapital.ae',
                'admin@zedcapital.ae',
                'clientsupport@zedcapital.ae',
                'wbd@zedcapital.ae',
            ];

            $emailData = [
                'popNumber' => $pop->pop_number,
                'unitNumber' => $pop->unit_number,
                'amount' => $pop->amount,
                'projectName' => $pop->project_name,
                'developerName' => $developerName,
                'soaUrl' => url($pop->soa_docs_url),
                'popUrl' => url($pop->attachment_url),
                'receiptUrl' => url($pop->receipt_url),
            ];

            Mail::send('emails.soa-uploaded-notification', $emailData, function ($message) use ($adminEmails, $pop) {
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

            // Send email
            Mail::to($property->developer_email)->send(
                new POPDeveloperNotification($pop, $magicLink, $property->cc_emails)
            );

            // Update POP notification status
            $pop->notification_sent = true;
            $pop->notification_sent_at = now();
            $pop->save();

        } catch (\Exception $e) {
            \Log::error("Failed to send POP to developer: " . $e->getMessage());
            // Don't throw - just log the error so POP creation still succeeds
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FinanceNOC;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use App\Models\DevUser;
use App\Models\FinanceAccess;
use App\Events\FinanceNOCUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinanceNOCController extends Controller
{
    /**
     * Get all NOCs for a project
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

        $query = FinanceNOC::with(['creator:id,full_name,email', 'unit', 'attachments'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_name', $project);
        }

        $nocs = $query->get();

        return response()->json([
            'success' => true,
            'nocs' => $nocs->map(function ($noc) {
                return [
                    'id' => $noc->id,
                    'nocNumber' => $noc->noc_number,
                    'nocName' => $noc->noc_name,
                    'unitNumber' => $noc->unit_number,
                    'unitId' => $noc->unit_id,
                    'description' => $noc->description,
                    'documentUrl' => $noc->document_url,
                    'documentName' => $noc->document_name,
                    'date' => $noc->created_at->format('Y-m-d'),
                    'notificationSent' => $noc->notification_sent,
                    'viewedByDeveloper' => $noc->viewed_by_developer,
                    'viewedAt' => $noc->viewed_at?->format('Y-m-d H:i:s'),
                    'sentToBuyer' => $noc->sent_to_buyer,
                    'sentToBuyerAt' => $noc->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'notes' => $noc->notes,
                    'attachments' => $noc->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'fileName' => $att->file_name,
                            'fileUrl' => $att->file_url,
                            'fileSize' => $att->file_size,
                            'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'timeline' => $noc->timeline,
                ];
            }),
        ]);
    }

    /**
     * Store a new NOC
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'noc_name' => 'required|string|max:255',
            'project_name' => 'required|string',
            'unit_number' => 'required|string',
            'description' => 'nullable|string',
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

            // Get project prefix from property
            $property = Property::where('project_name', $request->project_name)->first();
            $prefix = $property && $property->code_prefix ? $property->code_prefix : 'PROJ';

            // Generate NOC number with project prefix
            $lastNOC = FinanceNOC::where('project_name', $request->project_name)
                ->where('noc_number', 'like', $prefix . '-NOC-%')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastNOC) {
                preg_match('/' . preg_quote($prefix, '/') . '-NOC-(\d+)/', $lastNOC->noc_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nocNumber = $prefix . '-NOC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create the NOC record
            $noc = FinanceNOC::create([
                'noc_number' => $nocNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'noc_name' => $request->noc_name,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Send email to developer if requested
            if ($request->input('send_to_developer') === 'true') {
                $this->sendNOCNotificationToDeveloper($noc);
            }

            $noc->load('creator:id,full_name,email', 'unit');

            $nocData = [
                'id' => $noc->id,
                'nocNumber' => $noc->noc_number,
                'nocName' => $noc->noc_name,
                'unitNumber' => $noc->unit_number,
                'unitId' => $noc->unit_id,
                'description' => $noc->description,
                'documentUrl' => $noc->document_url,
                'documentName' => $noc->document_name,
                'date' => $noc->created_at->format('Y-m-d'),
                'notificationSent' => $noc->notification_sent,
                'viewedByDeveloper' => $noc->viewed_by_developer,
                'viewedAt' => $noc->viewed_at?->format('Y-m-d H:i:s'),
                'timeline' => $noc->timeline,
            ];

            // Broadcast the event
            Log::info('Broadcasting NOC created event', [
                'project' => $request->project_name,
                'noc_number' => $nocData['nocNumber'],
                'channel' => 'finance.' . str_replace(' ', '-', strtolower($request->project_name))
            ]);
            broadcast(new FinanceNOCUpdated($request->project_name, 'created', $nocData));

            // Broadcast pending counts update to developers with access to this project
            $this->broadcastPendingCountsForProject($request->project_name);

            return response()->json([
                'success' => true,
                'message' => $request->input('send_to_developer') === 'true' 
                    ? 'NOC created and notification sent to developer' 
                    : 'NOC created successfully',
                'noc' => $nocData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create NOC: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload NOC document (Developer)
     */
    public function uploadDocument(Request $request, $id)
    {
        $noc = FinanceNOC::find($id);

        if (!$noc) {
            return response()->json([
                'success' => false,
                'message' => 'NOC not found',
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
            $uploaderName = ($authType === 'developer' && $request->user()) ? $request->user()->name : 'Developer';

            // Store document with organized structure
            $fileName = 'NOC_' . $noc->noc_number . '_' . time() . '.' . $docFile->getClientOriginalExtension();
            $storagePath = 'finance/' . $noc->project_name . '/' . $noc->unit_number;
            
            // Delete old document if exists
            if ($noc->document_path && Storage::disk('public')->exists($noc->document_path)) {
                Storage::disk('public')->delete($noc->document_path);
            }
            
            $docPath = $docFile->storeAs($storagePath, $fileName, 'public');

            // Update NOC with document info
            $noc->document_path = $docPath;
            $noc->document_name = $fileName;
            $noc->document_uploaded_at = now();
            $noc->document_uploaded_by = $uploaderName;
            $noc->save();

            // Send notification to admin team
            $this->sendNOCDocumentUploadedNotificationToAdmin($noc, $uploaderName);

            // Broadcast pending counts update to developers with access
            if ($authType === 'developer') {
                $this->broadcastPendingCountsForProject($noc->project_name);
            }

            $noc->load('creator:id,full_name,email', 'unit');

            $nocData = [
                'id' => $noc->id,
                'nocNumber' => $noc->noc_number,
                'nocName' => $noc->noc_name,
                'unitNumber' => $noc->unit_number,
                'unitId' => $noc->unit_id,
                'description' => $noc->description,
                'documentUrl' => $noc->document_url,
                'documentName' => $noc->document_name,
                'date' => $noc->created_at->format('Y-m-d'),
                'notificationSent' => $noc->notification_sent,
                'viewedByDeveloper' => $noc->viewed_by_developer,
                'viewedAt' => $noc->viewed_at?->format('Y-m-d H:i:s'),
                'timeline' => $noc->timeline,
            ];

            // Broadcast the event
            broadcast(new FinanceNOCUpdated($noc->project_name, 'uploaded', $nocData));

            return response()->json([
                'success' => true,
                'message' => 'NOC document uploaded successfully',
                'noc' => $nocData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a NOC
     */
    public function destroy($id)
    {
        $noc = FinanceNOC::find($id);

        if (!$noc) {
            return response()->json([
                'success' => false,
                'message' => 'NOC not found',
            ], 404);
        }

        try {
            $projectName = $noc->project_name;
            $noc->delete();

            // Broadcast the event
            broadcast(new FinanceNOCUpdated($projectName, 'deleted', ['id' => $id]));

            return response()->json([
                'success' => true,
                'message' => 'NOC deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete NOC: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend NOC notification to developer
     */
    public function resendNotification($id)
    {
        $noc = FinanceNOC::find($id);

        if (!$noc) {
            return response()->json([
                'success' => false,
                'message' => 'NOC not found',
            ], 404);
        }

        try {
            $this->sendNOCNotificationToDeveloper($noc);

            return response()->json([
                'success' => true,
                'message' => 'NOC notification resent to developer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark NOC as viewed by developer
     */
    public function markAsViewed(Request $request, $id)
    {
        try {
            $noc = FinanceNOC::findOrFail($id);

            // Only mark as viewed if not already viewed
            if (!$noc->viewed_by_developer) {
                $noc->viewed_by_developer = true;
                $noc->viewed_at = now();
                $noc->save();

                $noc->load('creator:id,full_name,email', 'unit');

                $nocData = [
                    'id' => $noc->id,
                    'nocNumber' => $noc->noc_number,
                    'nocName' => $noc->noc_name,
                    'unitNumber' => $noc->unit_number,
                    'unitId' => $noc->unit_id,
                    'description' => $noc->description,
                    'documentUrl' => $noc->document_url,
                    'documentName' => $noc->document_name,
                    'date' => $noc->created_at->format('Y-m-d'),
                    'notificationSent' => $noc->notification_sent,
                    'viewedByDeveloper' => $noc->viewed_by_developer,
                    'viewedAt' => $noc->viewed_at?->format('Y-m-d H:i:s'),
                    'timeline' => $noc->timeline,
                ];

                // Broadcast the view event
                broadcast(new FinanceNOCUpdated($noc->project_name, 'viewed', $nocData));
            }

            return response()->json([
                'success' => true,
                'message' => 'NOC marked as viewed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as viewed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send NOC notification to developer
     */
    private function sendNOCNotificationToDeveloper(FinanceNOC $noc)
    {
        try {
            // Get property/project details to find developer email
            $property = Property::where('project_name', $noc->project_name)->first();
            
            if (!$property || !$property->developer_email) {
                Log::warning("No developer email found for project: {$noc->project_name}");
                return;
            }

            // Generate or get existing magic link
            $magicLink = DeveloperMagicLink::where('project_name', $noc->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $noc->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90
                );
            }

            // Prepare email data
            $emailData = [
                'subject' => "NOC Request: {$noc->noc_name} - Unit {$noc->unit_number}",
                'transactionType' => 'NOC Request',
                'developerName' => $property->developer_name ?? 'Developer',
                'messageBody' => 'We are requesting a No Objection Certificate for the property mentioned below. Please review and upload the required documents at your earliest convenience.',
                'details' => [
                    'NOC Number' => $noc->noc_number,
                    'NOC Name' => $noc->noc_name,
                    'Unit Number' => $noc->unit_number,
                    'Description' => $noc->description,
                    'Project' => $noc->project_name,
                ],
                'magicLink' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::mailer('finance')->send('emails.finance-to-developer', $emailData, function ($message) use ($property, $ccEmails, $noc) {
                $message->to($property->developer_email)
                    ->subject("NOC Request: {$noc->noc_name} - Unit {$noc->unit_number}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

            // Update NOC notification status
            $noc->notification_sent = true;
            $noc->notification_sent_at = now();
            $noc->save();

        } catch (\Exception $e) {
            Log::error("Failed to send NOC notification to developer: " . $e->getMessage());
        }
    }

    /**
     * Send NOC document uploaded notification to admin
     */
    private function sendNOCDocumentUploadedNotificationToAdmin(FinanceNOC $noc, string $developerName)
    {
        try {
            $adminEmails = [
                'wbd@zedcapital.ae'
            ];

            $emailData = [
                'subject' => "NOC Document Uploaded: {$noc->noc_name} - Unit {$noc->unit_number}",
                'greeting' => 'NOC Document Upload Notification',
                'transactionType' => 'NOC Document',
                'messageBody' => "A No Objection Certificate has been uploaded by {$developerName} for the following property.",
                'details' => [
                    'NOC Number' => $noc->noc_number,
                    'NOC Name' => $noc->noc_name,
                    'Unit Number' => $noc->unit_number,
                    'Project' => $noc->project_name,
                    'Uploaded By' => $developerName,
                ],
                'buttonUrl' => url($noc->document_url),
                'buttonText' => 'View NOC Document',
            ];

            Mail::mailer('finance')->send('emails.finance-to-admin', $emailData, function ($message) use ($adminEmails, $noc) {
                $message->to($adminEmails)
                    ->subject("NOC Document Uploaded: {$noc->noc_name} - Unit {$noc->unit_number}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to send NOC document uploaded notification: " . $e->getMessage());
        }
    }

    /**
     * Send NOC document to buyer via finance email
     */
    public function sendToBuyer(Request $request, $id)
    {
        $noc = FinanceNOC::with('unit.primaryFinanceEmail')->find($id);

        if (!$noc) {
            return response()->json([
                'success' => false,
                'message' => 'NOC not found',
            ], 404);
        }

        if (!$noc->document_path) {
            return response()->json([
                'success' => false,
                'message' => 'NOC document has not been uploaded yet',
            ], 400);
        }

        try {
            // Get buyer email and name from request or unit finance emails
            $buyerEmail = $request->input('to_email');
            $buyerName = $request->input('recipient_name');

            // If not provided in request, get from unit
            if (!$buyerEmail && $noc->unit && $noc->unit->primaryFinanceEmail) {
                $financeEmail = $noc->unit->primaryFinanceEmail;
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
            if ($request->input('to_email') && $noc->unit_id) {
                \App\Models\FinanceEmail::updateOrCreate(
                    [
                        'unit_id' => $noc->unit_id,
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
                'subject' => "NOC Document - {$noc->noc_name}",
                'transactionType' => 'No Objection Certificate',
                'buyerName' => $buyerName,
                'messageBody' => 'Please find attached your No Objection Certificate for the property mentioned below.',
                'details' => [
                    'NOC Number' => $noc->noc_number,
                    'NOC Name' => $noc->noc_name,
                    'Unit Number' => $noc->unit_number,
                    'Project' => $noc->project_name,
                ],
                'buttonUrl' => url('api/storage/' . $noc->document_path),
                'buttonText' => 'View NOC Document',
            ];

            // Send email with NOC document attachment
            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($buyerEmail, $noc) {
                $message->to($buyerEmail)
                    ->subject("NOC Document - {$noc->noc_name}");
                
                // Attach NOC document if it exists
                if ($noc->document_path && \Storage::disk('public')->exists($noc->document_path)) {
                    $filePath = storage_path('app/public/' . $noc->document_path);
                    $message->attach($filePath, [
                        'as' => $noc->document_name ?? 'NOC_Document.pdf',
                        'mime' => mime_content_type($filePath)
                    ]);
                }
            });

            // Update NOC
            $noc->sent_to_buyer = true;
            $noc->sent_to_buyer_at = now();
            $noc->sent_to_buyer_email = $buyerEmail;
            $noc->save();

            $nocData = [
                'id' => $noc->id,
                'nocNumber' => $noc->noc_number,
                'nocName' => $noc->noc_name,
                'unitNumber' => $noc->unit_number,
                'unitId' => $noc->unit_id,
                'documentUrl' => $noc->document_url,
                'documentName' => $noc->document_name,
                'date' => $noc->created_at->format('Y-m-d'),
                'sentToBuyer' => $noc->sent_to_buyer,
                'sentToBuyerAt' => $noc->sent_to_buyer_at?->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'NOC sent to buyer successfully',
                'noc' => $nocData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send NOC to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload attachment to NOC
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
            $noc = FinanceNOC::findOrFail($id);
            $file = $request->file('attachment');
            
            // Generate unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('finance/noc/attachments', $filename, 'public');
            
            // Create attachment record
            $attachment = $noc->attachments()->create([
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
     * Delete attachment from NOC
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $noc = FinanceNOC::findOrFail($id);
            $attachment = $noc->attachments()->findOrFail($attachmentId);
            
            // Delete file from storage
            \Storage::disk('public')->delete($attachment->file_path);
            
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
     * Broadcast pending counts to all developers with access to a project
     */
    private function broadcastPendingCountsForProject(string $projectName)
    {
        try {
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

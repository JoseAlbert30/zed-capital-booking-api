<?php

namespace App\Http\Controllers;

use App\Models\FinanceSOA;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use App\Events\FinanceSOAUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinanceSOAController extends Controller
{
    /**
     * Get all SOAs for a project
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

        $query = FinanceSOA::with(['creator:id,full_name,email', 'unit'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_name', $project);
        }

        $soas = $query->get();

        return response()->json([
            'success' => true,
            'soas' => $soas->map(function ($soa) {
                return [
                    'id' => $soa->id,
                    'soaNumber' => $soa->soa_number,
                    'unitNumber' => $soa->unit_number,
                    'unitId' => $soa->unit_id,
                    'description' => $soa->description,
                    'documentUrl' => $soa->document_url,
                    'documentName' => $soa->document_name,
                    'date' => $soa->created_at->format('Y-m-d'),
                    'notificationSent' => $soa->notification_sent,
                    'viewedByDeveloper' => $soa->viewed_by_developer,
                    'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                    'sentToBuyer' => $soa->sent_to_buyer,
                    'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'timeline' => $soa->timeline,
                ];
            }),
        ]);
    }

    /**
     * Store a new SOA
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string',
            'unit_number' => 'required|string',
            'description' => 'nullable|string',
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

            // Generate SOA number
            $lastSOA = FinanceSOA::orderBy('id', 'desc')->first();
            if ($lastSOA) {
                preg_match('/SOA-(\d+)/', $lastSOA->soa_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $soaNumber = 'SOA-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create the SOA record
            $soa = FinanceSOA::create([
                'soa_number' => $soaNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            // Send email to developer if requested
            if ($request->input('send_to_developer') === 'true') {
                $this->sendSOANotificationToDeveloper($soa);
            }

            $soa->load('creator:id,full_name,email', 'unit');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'description' => $soa->description,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'timeline' => $soa->timeline,
            ];

            // Broadcast the event
            Log::info('Broadcasting SOA created event', [
                'project' => $request->project_name,
                'soa_number' => $soaData['soaNumber'],
                'channel' => 'finance.' . str_replace(' ', '-', strtolower($request->project_name))
            ]);
            broadcast(new FinanceSOAUpdated($request->project_name, 'created', $soaData));

            return response()->json([
                'success' => true,
                'message' => $request->input('send_to_developer') === 'true' 
                    ? 'SOA request created and notification sent to developer' 
                    : 'SOA request created successfully',
                'soa' => $soaData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SOA request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload SOA document (Developer)
     */
    public function uploadDocument(Request $request, $id)
    {
        $soa = FinanceSOA::find($id);

        if (!$soa) {
            return response()->json([
                'success' => false,
                'message' => 'SOA request not found',
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
            $fileName = 'SOA_' . $soa->soa_number . '_' . time() . '.' . $docFile->getClientOriginalExtension();
            $storagePath = 'finance/' . $soa->project_name . '/' . $soa->unit_number;
            
            // Delete old document if exists
            if ($soa->document_path && Storage::disk('public')->exists($soa->document_path)) {
                Storage::disk('public')->delete($soa->document_path);
            }
            
            $docPath = $docFile->storeAs($storagePath, $fileName, 'public');

            // Update SOA with document info
            $soa->document_path = $docPath;
            $soa->document_name = $fileName;
            $soa->document_uploaded_at = now();
            $soa->document_uploaded_by = $developerName;
            $soa->save();

            // Send notification to admin team
            $this->sendSOADocumentUploadedNotificationToAdmin($soa, $developerName);

            $soa->load('creator:id,full_name,email', 'unit');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'description' => $soa->description,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'timeline' => $soa->timeline,
            ];

            // Broadcast the event
            broadcast(new FinanceSOAUpdated($soa->project_name, 'uploaded', $soaData));

            return response()->json([
                'success' => true,
                'message' => 'SOA document uploaded successfully',
                'soa' => $soaData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a SOA
     */
    public function destroy($id)
    {
        $soa = FinanceSOA::find($id);

        if (!$soa) {
            return response()->json([
                'success' => false,
                'message' => 'SOA request not found',
            ], 404);
        }

        try {
            $projectName = $soa->project_name;
            $soa->delete();

            // Broadcast the event
            broadcast(new FinanceSOAUpdated($projectName, 'deleted', ['id' => $id]));

            return response()->json([
                'success' => true,
                'message' => 'SOA request deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete SOA request: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resend SOA notification to developer
     */
    public function resendNotification($id)
    {
        $soa = FinanceSOA::find($id);

        if (!$soa) {
            return response()->json([
                'success' => false,
                'message' => 'SOA request not found',
            ], 404);
        }

        try {
            $this->sendSOANotificationToDeveloper($soa);

            return response()->json([
                'success' => true,
                'message' => 'SOA notification resent to developer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark SOA as viewed by developer
     */
    public function markAsViewed(Request $request, $id)
    {
        try {
            $soa = FinanceSOA::findOrFail($id);

            // Only mark as viewed if not already viewed
            if (!$soa->viewed_by_developer) {
                $soa->viewed_by_developer = true;
                $soa->viewed_at = now();
                $soa->save();

                $soa->load('creator:id,full_name,email', 'unit');

                $soaData = [
                    'id' => $soa->id,
                    'soaNumber' => $soa->soa_number,
                    'unitNumber' => $soa->unit_number,
                    'unitId' => $soa->unit_id,
                    'description' => $soa->description,
                    'documentUrl' => $soa->document_url,
                    'documentName' => $soa->document_name,
                    'date' => $soa->created_at->format('Y-m-d'),
                    'notificationSent' => $soa->notification_sent,
                    'viewedByDeveloper' => $soa->viewed_by_developer,
                    'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                    'timeline' => $soa->timeline,
                ];

                // Broadcast the view event
                broadcast(new FinanceSOAUpdated($soa->project_name, 'viewed', $soaData));
            }

            return response()->json([
                'success' => true,
                'message' => 'SOA marked as viewed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as viewed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send SOA notification to developer
     */
    private function sendSOANotificationToDeveloper(FinanceSOA $soa)
    {
        try {
            // Get property/project details to find developer email
            $property = Property::where('project_name', $soa->project_name)->first();
            
            if (!$property || !$property->developer_email) {
                Log::warning("No developer email found for project: {$soa->project_name}");
                return;
            }

            // Generate or get existing magic link
            $magicLink = DeveloperMagicLink::where('project_name', $soa->project_name)
                ->where('developer_email', $property->developer_email)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $soa->project_name,
                    $property->developer_email,
                    $property->developer_name ?? null,
                    90
                );
            }

            // Prepare email data
            $emailData = [
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'description' => $soa->description,
                'projectName' => $soa->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.soa-request-notification', $emailData, function ($message) use ($property, $ccEmails, $soa) {
                $message->to($property->developer_email)
                    ->subject("SOA Request: Unit {$soa->unit_number} - {$soa->project_name}");
                
                if (!empty($ccEmails)) {
                    $message->cc($ccEmails);
                }
            });

            // Update SOA notification status
            $soa->notification_sent = true;
            $soa->notification_sent_at = now();
            $soa->save();

        } catch (\Exception $e) {
            Log::error("Failed to send SOA notification to developer: " . $e->getMessage());
        }
    }

    /**
     * Send SOA document uploaded notification to admin
     */
    private function sendSOADocumentUploadedNotificationToAdmin(FinanceSOA $soa, string $developerName)
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
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'projectName' => $soa->project_name,
                'developerName' => $developerName,
                'documentUrl' => url($soa->document_url),
            ];

            Mail::send('emails.soa-document-uploaded-notification', $emailData, function ($message) use ($adminEmails, $soa) {
                $message->to($adminEmails)
                    ->subject("SOA Document Uploaded: Unit {$soa->unit_number} - {$soa->project_name}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to send SOA document uploaded notification: " . $e->getMessage());
        }
    }

    /**
     * Send SOA document to buyer
     */
    public function sendToBuyer(Request $request, $id)
    {
        $soa = FinanceSOA::with('unit.primaryFinanceEmail')->find($id);

        if (!$soa) {
            return response()->json([
                'success' => false,
                'message' => 'SOA not found',
            ], 404);
        }

        if (!$soa->document_path) {
            return response()->json([
                'success' => false,
                'message' => 'SOA document has not been uploaded yet',
            ], 400);
        }

        try {
            // Get buyer email from unit finance emails
            $buyerEmail = null;
            $buyerName = 'Buyer';

            if ($soa->unit && $soa->unit->primaryFinanceEmail) {
                $financeEmail = $soa->unit->primaryFinanceEmail;
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
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'projectName' => $soa->project_name,
                'documentUrl' => url('api/storage/' . $soa->document_path),
            ];

            // Send email
            Mail::send('emails.soa-sent-to-buyer', $emailData, function ($message) use ($buyerEmail, $soa) {
                $message->to($buyerEmail)
                    ->subject("Statement of Account - Unit {$soa->unit_number}");
            });

            // Update SOA
            $soa->sent_to_buyer = true;
            $soa->sent_to_buyer_at = now();
            $soa->sent_to_buyer_email = $buyerEmail;
            $soa->save();

            $soa->load('creator:id,full_name,email', 'unit');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'description' => $soa->description,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'sentToBuyer' => $soa->sent_to_buyer,
                'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                'timeline' => $soa->timeline,
            ];

            // Broadcast the event
            broadcast(new FinanceSOAUpdated($soa->project_name, 'sent_to_buyer', $soaData));

            return response()->json([
                'success' => true,
                'message' => 'SOA sent to buyer successfully',
                'soa' => $soaData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SOA to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FinanceNOC;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
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
        
        if ($authType === 'developer') {
            // Developer is authenticated via magic link - restrict to their project
            $project = $request->attributes->get('developer_project');
        }

        $query = FinanceNOC::with(['creator:id,full_name,email', 'unit'])
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

            // Generate NOC number
            $lastNOC = FinanceNOC::orderBy('id', 'desc')->first();
            if ($lastNOC) {
                preg_match('/NOC-(\d+)/', $lastNOC->noc_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $nocNumber = 'NOC-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create the NOC record
            $noc = FinanceNOC::create([
                'noc_number' => $nocNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'noc_name' => $request->noc_name,
                'description' => $request->description,
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
            $developerName = 'Developer';
            
            if ($authType === 'developer') {
                $developerName = $request->attributes->get('developer_name') ?? 'Developer';
            }

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
            $noc->document_uploaded_by = $developerName;
            $noc->save();

            // Send notification to admin team
            $this->sendNOCDocumentUploadedNotificationToAdmin($noc, $developerName);

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
                'nocNumber' => $noc->noc_number,
                'nocName' => $noc->noc_name,
                'unitNumber' => $noc->unit_number,
                'description' => $noc->description,
                'projectName' => $noc->project_name,
                'developerName' => $property->developer_name ?? 'Developer',
                'magicLink' => env('FRONTEND_URL', 'http://localhost:3000') . '/developer/login?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::send('emails.noc-request-notification', $emailData, function ($message) use ($property, $ccEmails, $noc) {
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
                'operations@zedcapital.ae',
                'docs@zedcapital.ae',
                'admin@zedcapital.ae',
                'clientsupport@zedcapital.ae',
                'wbd@zedcapital.ae',
            ];

            $emailData = [
                'nocNumber' => $noc->noc_number,
                'nocName' => $noc->noc_name,
                'unitNumber' => $noc->unit_number,
                'projectName' => $noc->project_name,
                'developerName' => $developerName,
                'documentUrl' => url($noc->document_url),
            ];

            Mail::send('emails.noc-document-uploaded-notification', $emailData, function ($message) use ($adminEmails, $noc) {
                $message->to($adminEmails)
                    ->subject("NOC Document Uploaded: {$noc->noc_name} - Unit {$noc->unit_number}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to send NOC document uploaded notification: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FinanceSOA;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use App\Models\DevUser;
use App\Models\FinanceAccess;
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

        $query = FinanceSOA::with(['creator:id,full_name,email', 'unit', 'attachments'])
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
                    'notes' => $soa->notes,
                    'attachments' => $soa->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'fileName' => $att->file_name,
                            'fileUrl' => $att->file_url,
                            'fileSize' => $att->file_size,
                            'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
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

            // Generate SOA number with project prefix
            $lastSOA = FinanceSOA::where('project_name', $request->project_name)
                ->where('soa_number', 'like', $prefix . '-SOA-%')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastSOA) {
                preg_match('/' . preg_quote($prefix, '/') . '-SOA-(\d+)/', $lastSOA->soa_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $soaNumber = $prefix . '-SOA-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Create the SOA record
            $soa = FinanceSOA::create([
                'soa_number' => $soaNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Send email to developer if requested
            if ($request->input('send_to_developer') === 'true') {
                $this->sendSOANotificationToDeveloper($soa);
            }

            $soa->load('creator:id,full_name,email', 'unit', 'attachments');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'projectName' => $soa->project_name,
                'description' => $soa->description,
                'notes' => $soa->notes,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'sentToBuyer' => (bool) $soa->sent_to_buyer,
                'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                'attachments' => $soa->attachments->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'fileName' => $att->file_name,
                        'fileUrl' => $att->file_url,
                        'fileSize' => $att->file_size,
                        'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'timeline' => $soa->timeline,
            ];

            // Broadcast the event
            Log::info('Broadcasting SOA created event', [
                'project' => $request->project_name,
                'soa_number' => $soaData['soaNumber'],
                'channel' => 'finance.' . str_replace(' ', '-', strtolower($request->project_name))
            ]);
            broadcast(new FinanceSOAUpdated($request->project_name, 'created', $soaData));

            // Broadcast pending counts update to developers with access to this project
            $this->broadcastPendingCountsForProject($request->project_name);

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

            // Broadcast pending counts update to developers with access
            if ($authType === 'developer') {
                $this->broadcastPendingCountsForProject($soa->project_name);
            }

            $soa->load('creator:id,full_name,email', 'unit', 'attachments');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'projectName' => $soa->project_name,
                'description' => $soa->description,
                'notes' => $soa->notes,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'sentToBuyer' => (bool) $soa->sent_to_buyer,
                'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                'attachments' => $soa->attachments->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'fileName' => $att->file_name,
                        'fileUrl' => $att->file_url,
                        'fileSize' => $att->file_size,
                        'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
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

                $soa->load('creator:id,full_name,email', 'unit', 'attachments');

                $soaData = [
                    'id' => $soa->id,
                    'soaNumber' => $soa->soa_number,
                    'unitNumber' => $soa->unit_number,
                    'unitId' => $soa->unit_id,
                    'projectName' => $soa->project_name,
                    'description' => $soa->description,
                    'notes' => $soa->notes,
                    'documentUrl' => $soa->document_url,
                    'documentName' => $soa->document_name,
                    'date' => $soa->created_at->format('Y-m-d'),
                    'notificationSent' => $soa->notification_sent,
                    'viewedByDeveloper' => $soa->viewed_by_developer,
                    'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                    'sentToBuyer' => (bool) $soa->sent_to_buyer,
                    'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'attachments' => $soa->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'fileName' => $att->file_name,
                            'fileUrl' => $att->file_url,
                            'fileSize' => $att->file_size,
                            'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
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
                'subject' => "SOA Request: Unit {$soa->unit_number} - {$soa->project_name}",
                'transactionType' => 'SOA Request',
                'developerName' => $property->developer_name ?? 'Developer',
                'messageBody' => 'We are requesting a Statement of Account for the property mentioned below. Please review and upload the required documents at your earliest convenience.',
                'details' => [
                    'SOA Number' => $soa->soa_number,
                    'Unit Number' => $soa->unit_number,
                    'Description' => $soa->description,
                    'Project' => $soa->project_name,
                ],
                'magicLink' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
            ];

            // Prepare CC emails
            $ccEmails = [];
            if ($property->cc_emails) {
                $ccEmails = array_map('trim', explode(',', $property->cc_emails));
            }

            Mail::mailer('finance')->send('emails.finance-to-developer', $emailData, function ($message) use ($property, $ccEmails, $soa) {
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
                'wbd@zedcapital.ae'
            ];

            $emailData = [
                'subject' => "SOA Document Uploaded: Unit {$soa->unit_number} - {$soa->project_name}",
                'greeting' => 'SOA Document Upload Notification',
                'transactionType' => 'SOA Document',
                'messageBody' => "A Statement of Account has been uploaded by {$developerName} for the following property.",
                'details' => [
                    'SOA Number' => $soa->soa_number,
                    'Unit Number' => $soa->unit_number,
                    'Project' => $soa->project_name,
                    'Uploaded By' => $developerName,
                ],
                'buttonUrl' => url($soa->document_url),
                'buttonText' => 'View SOA Document',
            ];

            Mail::mailer('finance')->send('emails.finance-to-admin', $emailData, function ($message) use ($adminEmails, $soa) {
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
            // Get buyer email and name from request or unit finance emails
            $buyerEmail = $request->input('to_email');
            $buyerName = $request->input('recipient_name');

            // If not provided in request, get from unit
            if (!$buyerEmail && $soa->unit && $soa->unit->primaryFinanceEmail) {
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

            // Save/update finance email if provided in request
            if ($request->input('to_email') && $soa->unit_id) {
                \App\Models\FinanceEmail::updateOrCreate(
                    [
                        'unit_id' => $soa->unit_id,
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
                'subject' => "Statement of Account - Unit {$soa->unit_number}",
                'transactionType' => 'Statement of Account',
                'buyerName' => $buyerName,
                'messageBody' => 'Please find attached your Statement of Account for the property mentioned below. Review the details carefully.',
                'details' => [
                    'SOA Number' => $soa->soa_number,
                    'Unit Number' => $soa->unit_number,
                    'Project' => $soa->project_name,
                ],
                'buttonUrl' => url('api/storage/' . $soa->document_path),
                'buttonText' => 'View SOA Document',
            ];

            // Send email
            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($buyerEmail, $soa) {
                $message->to($buyerEmail)
                    ->subject("Statement of Account - Unit {$soa->unit_number}");
            });

            // Update SOA
            $soa->sent_to_buyer = true;
            $soa->sent_to_buyer_at = now();
            $soa->sent_to_buyer_email = $buyerEmail;
            $soa->save();

            $soa->load('creator:id,full_name,email', 'unit', 'attachments');

            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'unitId' => $soa->unit_id,
                'projectName' => $soa->project_name,
                'description' => $soa->description,
                'notes' => $soa->notes,
                'documentUrl' => $soa->document_url,
                'documentName' => $soa->document_name,
                'date' => $soa->created_at->format('Y-m-d'),
                'notificationSent' => $soa->notification_sent,
                'viewedByDeveloper' => $soa->viewed_by_developer,
                'viewedAt' => $soa->viewed_at?->format('Y-m-d H:i:s'),
                'sentToBuyer' => (bool) $soa->sent_to_buyer,
                'sentToBuyerAt' => $soa->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                'attachments' => $soa->attachments->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'fileName' => $att->file_name,
                        'fileUrl' => $att->file_url,
                        'fileSize' => $att->file_size,
                        'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
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

    /**
     * Resend SOA to buyer
     */
    public function resendToBuyer(Request $request, $id)
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

        if (!$soa->sent_to_buyer) {
            return response()->json([
                'success' => false,
                'message' => 'SOA has not been sent to buyer yet',
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
                'subject' => "Statement of Account - Unit {$soa->unit_number}",
                'transactionType' => 'Statement of Account',
                'buyerName' => $buyerName,
                'messageBody' => 'Please find attached your Statement of Account for the property mentioned below. Review the details carefully.',
                'details' => [
                    'SOA Number' => $soa->soa_number,
                    'Unit Number' => $soa->unit_number,
                    'Project' => $soa->project_name,
                ],
                'buttonUrl' => url('api/storage/' . $soa->document_path),
                'buttonText' => 'View SOA Document',
            ];

            // Send email with SOA document attachment
            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($buyerEmail, $soa) {
                $message->to($buyerEmail)
                    ->subject("Statement of Account - Unit {$soa->unit_number}");
                
                // Attach SOA document if it exists
                if ($soa->document_path && \Storage::disk('public')->exists($soa->document_path)) {
                    $filePath = storage_path('app/public/' . $soa->document_path);
                    $message->attach($filePath, [
                        'as' => $soa->document_name ?? 'SOA_Document.pdf',
                        'mime' => mime_content_type($filePath)
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'SOA resent to buyer successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend SOA to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload attachment to SOA
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
            $soa = FinanceSOA::findOrFail($id);
            $file = $request->file('attachment');
            
            // Generate unique filename
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('finance/soa/attachments', $filename, 'public');
            
            // Create attachment record
            $attachment = $soa->attachments()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            // Reload SOA with attachments to get updated data
            $soa = FinanceSOA::with('attachments')->find($id);
            
            $soaData = [
                'id' => $soa->id,
                'soaNumber' => $soa->soa_number,
                'unitNumber' => $soa->unit_number,
                'projectName' => $soa->project_name,
                'description' => $soa->description,
                'notes' => $soa->notes,
                'documentPath' => $soa->document_path ? asset('storage/' . $soa->document_path) : null,
                'documentName' => $soa->document_name,
                'documentSize' => $soa->document_size,
                'uploadedAt' => $soa->uploaded_at ? $soa->uploaded_at->format('Y-m-d H:i:s') : null,
                'sentToBuyer' => (bool) $soa->sent_to_buyer,
                'sentAt' => $soa->sent_at ? $soa->sent_at->format('Y-m-d H:i:s') : null,
                'viewedByBuyer' => (bool) $soa->viewed_by_buyer,
                'viewedAt' => $soa->viewed_at ? $soa->viewed_at->format('Y-m-d H:i:s') : null,
                'createdAt' => $soa->created_at->format('Y-m-d H:i:s'),
                'attachments' => $soa->attachments->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'fileName' => $att->file_name,
                        'fileUrl' => $att->file_url,
                        'fileSize' => $att->file_size,
                        'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ];
            
            // Broadcast the update
            broadcast(new FinanceSOAUpdated($soa->project_name, 'attachment-added', $soaData));

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
     * Delete attachment from SOA
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $soa = FinanceSOA::findOrFail($id);
            $attachment = $soa->attachments()->findOrFail($attachmentId);
            
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

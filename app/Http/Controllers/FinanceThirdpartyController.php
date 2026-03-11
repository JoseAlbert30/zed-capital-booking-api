<?php

namespace App\Http\Controllers;

use App\Models\FinanceThirdparty;
use App\Models\Property;
use App\Models\DeveloperMagicLink;
use App\Models\DevUser;
use App\Models\FinanceAccess;
use App\Events\FinanceThirdpartyUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinanceThirdpartyController extends Controller
{
    /**
     * Get all thirdparties for a project
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

        $query = FinanceThirdparty::with(['creator:id,full_name,email', 'unit', 'attachments'])
            ->orderBy('created_at', 'desc');

        if ($project) {
            $query->where('project_name', $project);
        }

        $thirdparties = $query->get();

        return response()->json([
            'success' => true,
            'thirdparties' => $thirdparties->map(function ($tp) {
                return [
                    'id' => $tp->id,
                    'thirdpartyNumber' => $tp->thirdparty_number,
                    'thirdpartyName' => $tp->thirdparty_name,
                    'unitNumber' => $tp->unit_number,
                    'unitId' => $tp->unit_id,
                    'description' => $tp->description,
                    'formDocumentUrl' => $tp->form_document_url,
                    'formDocumentName' => $tp->form_document_name,
                    'signedDocumentUrl' => $tp->signed_document_url,
                    'signedDocumentName' => $tp->signed_document_name,
                    'receiptDocumentUrl' => $tp->receipt_document_url,
                    'receiptDocumentName' => $tp->receipt_document_name,
                    'date' => $tp->created_at->format('Y-m-d'),
                    'sentToBuyer' => $tp->sent_to_buyer,
                    'sentToBuyerAt' => $tp->sent_to_buyer_at?->format('Y-m-d H:i:s'),
                    'sentToDeveloper' => $tp->sent_to_developer,
                    'sentToDeveloperAt' => $tp->sent_to_developer_at?->format('Y-m-d H:i:s'),
                    'viewedByDeveloper' => $tp->viewed_by_developer,
                    'viewedAt' => $tp->viewed_at?->format('Y-m-d H:i:s'),
                    'notes' => $tp->notes,
                    'attachments' => $tp->attachments->map(function ($att) {
                        return [
                            'id' => $att->id,
                            'fileName' => $att->file_name,
                            'fileUrl' => $att->file_url,
                            'fileSize' => $att->file_size,
                            'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                    'timeline' => $tp->timeline,
                ];
            }),
        ]);
    }

    /**
     * Store a new thirdparty (with form upload)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'thirdparty_name' => 'required|string|max:255',
            'project_name' => 'required|string',
            'unit_number' => 'required|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'form_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
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

            // Generate thirdparty number with project prefix
            $lastTP = FinanceThirdparty::where('project_name', $request->project_name)
                ->where('thirdparty_number', 'like', $prefix . '-TP-%')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastTP) {
                preg_match('/' . preg_quote($prefix, '/') . '-TP-(\d+)/', $lastTP->thirdparty_number, $matches);
                $lastNumber = isset($matches[1]) ? intval($matches[1]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            $thirdpartyNumber = $prefix . '-TP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Upload form document
            $formDoc = $request->file('form_document');
            $fileName = 'Thirdparty_' . $thirdpartyNumber . '_Form_' . time() . '.' . $formDoc->getClientOriginalExtension();
            $storagePath = 'finance/' . $request->project_name . '/' . $request->unit_number;
            $formPath = $formDoc->storeAs($storagePath, $fileName, 'public');

            // Create the thirdparty record
            $thirdparty = FinanceThirdparty::create([
                'thirdparty_number' => $thirdpartyNumber,
                'project_name' => $request->project_name,
                'unit_id' => $unit->id,
                'unit_number' => $request->unit_number,
                'thirdparty_name' => $request->thirdparty_name,
                'description' => $request->description,
                'notes' => $request->notes,
                'form_document_path' => $formPath,
                'form_document_name' => $fileName,
                'form_uploaded_at' => now(),
                'created_by' => Auth::id(),
            ]);

            $thirdparty->load('creator:id,full_name,email', 'unit');

            $thirdpartyData = $this->formatThirdpartyData($thirdparty);

            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'created', $thirdpartyData));

            // Broadcast pending counts update to developers with access to this project
            $this->broadcastPendingCountsForProject($request->project_name);

            return response()->json([
                'success' => true,
                'message' => 'Thirdparty form created successfully',
                'thirdparty' => $thirdpartyData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create thirdparty: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a thirdparty
     */
    public function destroy($id)
    {
        $thirdparty = FinanceThirdparty::find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        try {
            $thirdparty->delete();

            broadcast(new \App\Events\FinanceThirdpartyUpdated($thirdparty->project_name, 'deleted', ['id' => $id, 'thirdpartyNumber' => $thirdparty->thirdparty_number]));

            return response()->json([
                'success' => true,
                'message' => 'Thirdparty deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete thirdparty: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send thirdparty form to buyer
     */
    public function sendToBuyer(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::with('unit.primaryFinanceEmail')->find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        if (!$thirdparty->form_document_path) {
            return response()->json([
                'success' => false,
                'message' => 'Form document has not been uploaded yet',
            ], 400);
        }

        try {
            // Get email addresses and name from request or defaults
            $toEmail = $request->input('to_email');
            $ccEmails = $request->input('cc_emails');
            $buyerName = $request->input('recipient_name');

            // If no email provided, get from unit
            if (!$toEmail && $thirdparty->unit && $thirdparty->unit->primaryFinanceEmail) {
                $financeEmail = $thirdparty->unit->primaryFinanceEmail;
                $toEmail = $financeEmail->email;
                $buyerName = $financeEmail->recipient_name ?? 'Buyer';
            }

            if (!$toEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No buyer email found for this unit',
                ], 400);
            }

            // Save/update finance email if provided in request
            if ($request->input('to_email') && $thirdparty->unit_id) {
                \App\Models\FinanceEmail::updateOrCreate(
                    [
                        'unit_id' => $thirdparty->unit_id,
                        'type' => 'buyer',
                        'is_primary' => true,
                    ],
                    [
                        'email' => $request->input('to_email'),
                        'recipient_name' => $request->input('recipient_name', 'Buyer'),
                    ]
                );
            }

            $emailData = [
                'subject' => "Thirdparty Form: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}",
                'transactionType' => 'Thirdparty Form',
                'buyerName' => $buyerName,
                'messageBody' => 'Please find attached the thirdparty form for your property. Review the document carefully.',
                'details' => [
                    'Thirdparty Number' => $thirdparty->thirdparty_number,
                    'Form Name' => $thirdparty->thirdparty_name,
                    'Unit Number' => $thirdparty->unit_number,
                    'Project' => $thirdparty->project_name,
                ],
                'buttonUrl' => url($thirdparty->form_document_url),
                'buttonText' => 'View Form Document',
            ];

            // Prepare CC emails array
            $ccEmailsArray = [];
            if ($ccEmails) {
                $ccEmailsArray = array_map('trim', explode(',', $ccEmails));
                $ccEmailsArray = array_filter($ccEmailsArray);
            }

            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($toEmail, $ccEmailsArray, $thirdparty) {
                $staticCc = ['wbd@zedcapital.ae', 'president@zedcapital.ae', 'finance@zedcapital.ae', 'accounting@zedcapital.ae', 'accounts@zedcapital.ae', 'operations@zedcapital.ae'];
                $allCc = array_values(array_unique(array_merge($staticCc, $ccEmailsArray)));
                $message->to($toEmail)
                    ->subject("Thirdparty Form: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}")
                    ->cc($allCc);

                // Attach form document if available
                if ($thirdparty->form_document_path && \Storage::disk('public')->exists($thirdparty->form_document_path)) {
                    $message->attach(\Storage::disk('public')->path($thirdparty->form_document_path));
                }
            });

            $thirdparty->sent_to_buyer = true;
            $thirdparty->sent_to_buyer_at = now();
            $thirdparty->sent_to_buyer_email = $toEmail;
            $thirdparty->save();

            // Reload with relationships to get updated timeline
            $thirdparty->load(['creator:id,full_name,email', 'unit', 'attachments']);

            // Broadcast event
            $thirdpartyBroadcastData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'sent-to-buyer', $thirdpartyBroadcastData));

            return response()->json([
                'success' => true,
                'message' => 'Thirdparty form sent to buyer successfully',
                'thirdparty' => $thirdparty,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send thirdparty to buyer: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send thirdparty to buyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload signed document from buyer
     */
    public function uploadSignedDocument(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'signed_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'attachments.*' => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $signedDoc = $request->file('signed_document');
            
            $fileName = 'Thirdparty_' . $thirdparty->thirdparty_number . '_Signed_' . time() . '.' . $signedDoc->getClientOriginalExtension();
            $storagePath = 'finance/' . $thirdparty->project_name . '/' . $thirdparty->unit_number;
            
            // Delete old signed document if exists
            if ($thirdparty->signed_document_path && Storage::disk('public')->exists($thirdparty->signed_document_path)) {
                Storage::disk('public')->delete($thirdparty->signed_document_path);
            }
            
            $signedPath = $signedDoc->storeAs($storagePath, $fileName, 'public');

            $thirdparty->signed_document_path = $signedPath;
            $thirdparty->signed_document_name = $fileName;
            $thirdparty->signed_document_uploaded_at = now();
            $thirdparty->save();

            // Handle additional attachments (ID/Passport, Trade License, etc.)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    $attachmentName = 'Thirdparty_' . $thirdparty->thirdparty_number . '_Attachment_' . time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                    $attachmentPath = $attachment->storeAs($storagePath . '/attachments', $attachmentName, 'public');
                    
                    // Save attachment record using morphMany relationship
                    $thirdparty->attachments()->create([
                        'file_name' => $attachment->getClientOriginalName(),
                        'file_path' => $attachmentPath,
                        'file_type' => $attachment->getClientMimeType(),
                        'file_size' => $attachment->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            // Reload with relationships
            $thirdparty->load(['attachments']);

            // Broadcast event
            $thirdpartySignedData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'signed-uploaded', $thirdpartySignedData));

            return response()->json([
                'success' => true,
                'message' => 'Signed document and attachments uploaded successfully',
                'thirdparty' => $thirdparty,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upload signed document: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload signed document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send thirdparty to developer
     */
    public function sendToDeveloper(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::with('attachments')->find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        if (!$thirdparty->signed_document_path) {
            return response()->json([
                'success' => false,
                'message' => 'Signed document has not been uploaded yet',
            ], 400);
        }

        try {
            // Get email addresses from request or defaults
            $toEmail = $request->input('to_email');
            $ccEmails = $request->input('cc_emails');

            $property = Property::where('project_name', $thirdparty->project_name)->first();
            
            // If no email provided, get from property settings
            if (!$toEmail && $property && $property->developer_email) {
                $toEmail = $property->developer_email;
            }

            if (!$toEmail) {
                Log::warning("No developer email found for project: {$thirdparty->project_name}");
                return response()->json([
                    'success' => false,
                    'message' => 'No developer email found for this project',
                ], 400);
            }

            // If no CC provided, get from property settings
            if (!$ccEmails && $property && $property->cc_emails) {
                $ccEmails = $property->cc_emails;
            }

            // Generate or get existing magic link
            $magicLink = DeveloperMagicLink::where('project_name', $thirdparty->project_name)
                ->where('developer_email', $toEmail)
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->first();

            if (!$magicLink) {
                $magicLink = DeveloperMagicLink::generate(
                    $thirdparty->project_name,
                    $toEmail,
                    $property->developer_name ?? null,
                    90
                );
            }

            $emailData = [
                'subject' => "Thirdparty Document: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}",
                'transactionType' => 'Thirdparty Document',
                'developerName' => $property->developer_name ?? 'Developer',
                'messageBody' => 'A thirdparty document has been submitted and requires your attention. Please review and process the attached documents.',
                'details' => [
                    'Thirdparty Number' => $thirdparty->thirdparty_number,
                    'Form Name' => $thirdparty->thirdparty_name,
                    'Unit Number' => $thirdparty->unit_number,
                    'Project' => $thirdparty->project_name,
                ],
                'magicLink' => config('app.frontend_url') . '/developer/portal?token=' . $magicLink->token,
            ];

            // Prepare CC emails array
            $ccEmailsArray = [];
            if ($ccEmails) {
                $ccEmailsArray = array_map('trim', explode(',', $ccEmails));
                $ccEmailsArray = array_filter($ccEmailsArray);
            }

            Mail::mailer('finance')->send('emails.finance-to-developer', $emailData, function ($message) use ($toEmail, $ccEmailsArray, $thirdparty) {
                $message->to($toEmail)
                    ->subject("Thirdparty Document: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}");
                
                if (!empty($ccEmailsArray)) {
                    $message->cc($ccEmailsArray);
                }

                // Attach signed document
                if ($thirdparty->signed_document_path && \Storage::disk('public')->exists($thirdparty->signed_document_path)) {
                    $message->attach(\Storage::disk('public')->path($thirdparty->signed_document_path));
                }

                // Attach all additional attachments
                foreach ($thirdparty->attachments as $attachment) {
                    if (\Storage::disk('public')->exists($attachment->file_path)) {
                        $message->attach(\Storage::disk('public')->path($attachment->file_path));
                    }
                }
            });

            $thirdparty->sent_to_developer = true;
            $thirdparty->sent_to_developer_at = now();
            $thirdparty->save();

            $thirdparty->load(['attachments']);
            $thirdpartyDevData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'sent-to-developer', $thirdpartyDevData));

            return response()->json([
                'success' => true,
                'message' => 'Thirdparty sent to developer successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send thirdparty to developer: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send thirdparty to developer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload receipt from developer
     */
    public function uploadReceipt(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $receiptDoc = $request->file('receipt');
            
            // Get uploader name from authenticated user
            $authType = $request->attributes->get('auth_type');
            $uploaderName = ($authType === 'developer' && $request->user()) ? $request->user()->name : 'Developer';

            $fileName = 'Thirdparty_' . $thirdparty->thirdparty_number . '_Receipt_' . time() . '.' . $receiptDoc->getClientOriginalExtension();
            $storagePath = 'finance/' . $thirdparty->project_name . '/' . $thirdparty->unit_number;
            
            // Delete old receipt if exists
            if ($thirdparty->receipt_document_path && Storage::disk('public')->exists($thirdparty->receipt_document_path)) {
                Storage::disk('public')->delete($thirdparty->receipt_document_path);
            }
            
            $receiptPath = $receiptDoc->storeAs($storagePath, $fileName, 'public');

            $thirdparty->receipt_document_path = $receiptPath;
            $thirdparty->receipt_document_name = $fileName;
            $thirdparty->receipt_uploaded_at = now();
            $thirdparty->receipt_uploaded_by = $uploaderName;
            $thirdparty->save();

            // Broadcast pending counts update to developers with access
            if ($authType === 'developer') {
                $this->broadcastPendingCountsForProject($thirdparty->project_name);
            }

            $thirdparty->load(['attachments']);
            $thirdpartyReceiptData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'receipt-uploaded', $thirdpartyReceiptData));

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark thirdparty as viewed by developer
     */
    public function markAsViewed($id)
    {
        $thirdparty = FinanceThirdparty::find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        if ($thirdparty->viewed_by_developer) {
            return response()->json([
                'success' => true,
                'message' => 'Thirdparty already marked as viewed',
            ]);
        }

        try {
            $thirdparty->viewed_by_developer = true;
            $thirdparty->viewed_at = now();
            $thirdparty->save();

            $thirdparty->load(['attachments']);
            $thirdpartyViewedData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'viewed', $thirdpartyViewedData));

            return response()->json([
                'success' => true,
                'message' => 'Thirdparty marked as viewed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark thirdparty as viewed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload attachment
     */
    public function uploadAttachment(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'attachment' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('attachment');
            
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('finance/thirdparty/attachments', $filename, 'public');
            
            $attachment = $thirdparty->attachments()->create([
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            // Broadcast attachment-added event
            $thirdparty->load(['attachments']);
            $thirdpartyAttachData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'attachment-added', $thirdpartyAttachData));

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
     * Delete attachment
     */
    public function deleteAttachment($id, $attachmentId)
    {
        try {
            $thirdparty = FinanceThirdparty::findOrFail($id);
            $attachment = $thirdparty->attachments()->findOrFail($attachmentId);
            
            \Storage::disk('public')->delete($attachment->file_path);
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
     * Send receipt to buyer
     */
    public function sendReceiptToBuyer(Request $request, $id)
    {
        $thirdparty = FinanceThirdparty::with(['unit.primaryFinanceEmail'])->find($id);

        if (!$thirdparty) {
            return response()->json([
                'success' => false,
                'message' => 'Thirdparty not found',
            ], 404);
        }

        if (!$thirdparty->receipt_document_path) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt has not been uploaded yet',
            ], 400);
        }

        try {
            // Get email addresses and name from request or defaults
            $toEmail = $request->input('to_email');
            $ccEmails = $request->input('cc_emails');
            $buyerName = $request->input('recipient_name');

            // If no email provided, get from unit
            if (!$toEmail && $thirdparty->unit && $thirdparty->unit->primaryFinanceEmail) {
                $financeEmail = $thirdparty->unit->primaryFinanceEmail;
                $toEmail = $financeEmail->email;
                $buyerName = $financeEmail->recipient_name ?? 'Buyer';
            }

            if (!$toEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No buyer email found for this unit',
                ], 400);
            }

            // Save/update finance email if provided in request
            if ($request->input('to_email') && $thirdparty->unit_id) {
                \App\Models\FinanceEmail::updateOrCreate(
                    [
                        'unit_id' => $thirdparty->unit_id,
                        'type' => 'buyer',
                        'is_primary' => true,
                    ],
                    [
                        'email' => $request->input('to_email'),
                        'recipient_name' => $request->input('recipient_name', 'Buyer'),
                    ]
                );
            }

            $emailData = [
                'subject' => "Thirdparty Receipt: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}",
                'transactionType' => 'Thirdparty Receipt',
                'buyerName' => $buyerName,
                'messageBody' => 'Thank you for your payment. Please find attached your receipt for the thirdparty form.',
                'details' => [
                    'Thirdparty Number' => $thirdparty->thirdparty_number,
                    'Form Name' => $thirdparty->thirdparty_name,
                    'Unit Number' => $thirdparty->unit_number,
                    'Project' => $thirdparty->project_name,
                ],
                'buttonUrl' => url($thirdparty->receipt_document_url),
                'buttonText' => 'View Receipt',
            ];

            // Prepare CC emails array
            $ccEmailsArray = [];
            if ($ccEmails) {
                $ccEmailsArray = array_map('trim', explode(',', $ccEmails));
                $ccEmailsArray = array_filter($ccEmailsArray);
            }

            Mail::mailer('finance')->send('emails.finance-to-buyer', $emailData, function ($message) use ($toEmail, $ccEmailsArray, $thirdparty) {
                $staticCc = ['wbd@zedcapital.ae', 'president@zedcapital.ae', 'finance@zedcapital.ae', 'accounting@zedcapital.ae', 'accounts@zedcapital.ae', 'operations@zedcapital.ae'];
                $allCc = array_values(array_unique(array_merge($staticCc, $ccEmailsArray)));
                $message->to($toEmail)
                    ->subject("Thirdparty Receipt: {$thirdparty->thirdparty_name} - Unit {$thirdparty->unit_number}")
                    ->cc($allCc);

                // Attach receipt
                if ($thirdparty->receipt_document_path && \Storage::disk('public')->exists($thirdparty->receipt_document_path)) {
                    $message->attach(\Storage::disk('public')->path($thirdparty->receipt_document_path));
                }
            });

            $thirdparty->receipt_sent_to_buyer = true;
            $thirdparty->receipt_sent_to_buyer_at = now();
            $thirdparty->save();

            $thirdparty->load(['attachments']);
            $thirdpartyRcptBuyerData = $this->formatThirdpartyData($thirdparty);
            broadcast(new FinanceThirdpartyUpdated($thirdparty->project_name, 'receipt-sent-to-buyer', $thirdpartyRcptBuyerData));

            return response()->json([
                'success' => true,
                'message' => 'Receipt sent to buyer successfully',
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
     * Format thirdparty data for broadcast/response (consistent shape)
     */
    private function formatThirdpartyData(FinanceThirdparty $thirdparty): array
    {
        $thirdparty->loadMissing(['attachments']);

        return [
            'id' => $thirdparty->id,
            'thirdpartyNumber' => $thirdparty->thirdparty_number,
            'thirdpartyName' => $thirdparty->thirdparty_name,
            'unitNumber' => $thirdparty->unit_number,
            'unitId' => $thirdparty->unit_id,
            'projectName' => $thirdparty->project_name,
            'description' => $thirdparty->description,
            'notes' => $thirdparty->notes,
            'formDocumentUrl' => $thirdparty->form_document_url,
            'formDocumentName' => $thirdparty->form_document_name,
            'signedDocumentUrl' => $thirdparty->signed_document_url,
            'signedDocumentName' => $thirdparty->signed_document_name,
            'receiptDocumentUrl' => $thirdparty->receipt_document_url,
            'receiptDocumentName' => $thirdparty->receipt_document_name,
            'date' => $thirdparty->created_at->format('Y-m-d'),
            'notificationSent' => $thirdparty->notification_sent,
            'viewedByDeveloper' => $thirdparty->viewed_by_developer,
            'viewedAt' => $thirdparty->viewed_at?->format('Y-m-d H:i:s'),
            'sentToBuyer' => (bool) $thirdparty->sent_to_buyer,
            'sentToBuyerAt' => $thirdparty->sent_to_buyer_at?->format('Y-m-d H:i:s'),
            'sentToDeveloper' => (bool) $thirdparty->sent_to_developer,
            'sentToDeveloperAt' => $thirdparty->sent_to_developer_at?->format('Y-m-d H:i:s'),
            'receiptSentToBuyer' => (bool) $thirdparty->receipt_sent_to_buyer,
            'receiptSentToBuyerAt' => $thirdparty->receipt_sent_to_buyer_at?->format('Y-m-d H:i:s'),
            'attachments' => $thirdparty->attachments->map(function ($att) {
                return [
                    'id' => $att->id,
                    'fileName' => $att->file_name,
                    'fileUrl' => $att->file_url,
                    'fileSize' => $att->file_size,
                    'uploadedAt' => $att->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'timeline' => $thirdparty->timeline,
        ];
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


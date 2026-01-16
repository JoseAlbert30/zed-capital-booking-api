<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    /**
     * Get all units with their property and users.
     */
    public function index(Request $request)
    {
        try {
            $query = Unit::with(['property', 'users', 'attachments', 'booking']);

            // Filter by property if provided
            if ($request->has('property_id')) {
                $query->where('property_id', $request->property_id);
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search in unit number, owner names, or project name
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    // Search in unit number
                    $q->where('unit', 'like', "%{$searchTerm}%")
                      // Search in property/project name
                      ->orWhereHas('property', function($q) use ($searchTerm) {
                          $q->where('project_name', 'like', "%{$searchTerm}%");
                      })
                      // Search in owner names
                      ->orWhereHas('users', function($q) use ($searchTerm) {
                          $q->where('full_name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Filter by SOA status (has SOA attachment or not)
            if ($request->has('soa_status') && $request->soa_status !== 'all') {
                if ($request->soa_status === 'uploaded') {
                    $query->whereHas('attachments', function($q) {
                        $q->where('type', 'soa');
                    });
                } elseif ($request->soa_status === 'not_uploaded') {
                    $query->whereDoesntHave('attachments', function($q) {
                        $q->where('type', 'soa');
                    });
                }
            }

            // Filter by payment status
            if ($request->has('payment_status') && $request->payment_status !== 'all') {
                $query->where('payment_status', $request->payment_status);
            }

            // Filter by handover status (unit's actual handover status)
            if ($request->has('handover_status') && $request->handover_status !== 'all') {
                if ($request->handover_status === 'scheduled') {
                    // Scheduled = has a booking and handover not completed
                    $query->whereHas('bookings')
                          ->where('handover_status', '!=', 'completed');
                } else {
                    $query->where('handover_status', $request->handover_status);
                }
            }

            // Filter by handover requirements (handover_ready status)
            if ($request->has('handover_requirements') && $request->handover_requirements !== 'all') {
                if ($request->handover_requirements === 'complete') {
                    $query->where('handover_ready', true);
                } elseif ($request->handover_requirements === 'incomplete') {
                    $query->where('handover_ready', false);
                }
            }

            // Filter by booking status
            if ($request->has('booking_status') && $request->booking_status !== 'all') {
                if ($request->booking_status === 'booked') {
                    $query->whereHas('booking');
                } elseif ($request->booking_status === 'not_booked') {
                    $query->whereDoesntHave('booking');
                }
            }

            // Filter by booking date range
            if ($request->has('booking_date_from') || $request->has('booking_date_to')) {
                $query->whereHas('booking', function($q) use ($request) {
                    if ($request->has('booking_date_from')) {
                        $q->where('booked_date', '>=', $request->booking_date_from);
                    }
                    if ($request->has('booking_date_to')) {
                        $q->where('booked_date', '<=', $request->booking_date_to);
                    }
                });
            }

            // Filter occupied vs unoccupied (for Unit Management vs Unit Listing)
            if ($request->has('occupied')) {
                if ($request->occupied === 'true') {
                    $query->whereHas('users'); // Has at least one owner
                } elseif ($request->occupied === 'false') {
                    $query->whereDoesntHave('users'); // Has no owners
                }
            }

            $units = $query->get();

            return response()->json([
                'success' => true,
                'units' => $units
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch units', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a single unit by ID with all related data.
     */
    public function show($id)
    {
        try {
            $unit = Unit::with([
                'property',
                'users',
                'attachments.unit.property',
                'remarks',
                'bookings.user'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'unit' => $unit
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch unit', [
                'unit_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a single unit.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:properties,id',
                'unit' => 'required|string|max:255',
                'floor' => 'nullable|string|max:50',
                'building' => 'nullable|string|max:100',
                'square_footage' => 'nullable|numeric|min:0',
                'dewa_premise_number' => 'nullable|string|max:255',
                'status' => 'nullable|in:unclaimed,claimed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if unit already exists for this property
            $existingUnit = Unit::where('property_id', $request->property_id)
                ->where('unit', $request->unit)
                ->first();

            if ($existingUnit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit already exists for this property'
                ], 409);
            }

            $unit = Unit::create([
                'property_id' => $request->property_id,
                'unit' => $request->unit,
                'floor' => $request->floor,
                'building' => $request->building,
                'square_footage' => $request->square_footage,
                'dewa_premise_number' => $request->dewa_premise_number,
                'status' => $request->status ?? 'unclaimed'
            ]);

            $unit->load(['property', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Unit created successfully',
                'unit' => $unit
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create unit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk upload units from CSV/Excel file.
     * Expected CSV format: property_name, unit, floor, building, square_footage
     */
    public function bulkUpload(Request $request)
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
                if ($extension === 'csv' || $extension === 'txt') {
                    // Parse CSV
                    $handle = fopen($file->getRealPath(), 'r');
                    
                    // Skip header row
                    $header = fgetcsv($handle);
                    
                    while (($row = fgetcsv($handle)) !== false) {
                        $results['total']++;
                        
                        // Expected format: unit, floor, building, square_footage, dewa_premise_number
                        if (count($row) < 1) {
                            $results['errors'][] = "Row {$results['total']}: Invalid format";
                            $results['skipped']++;
                            continue;
                        }

                        $unitNumber = trim($row[0]);
                        $floor = isset($row[1]) ? trim($row[1]) : null;
                        $building = isset($row[2]) ? trim($row[2]) : null;
                        $squareFootage = isset($row[3]) ? trim($row[3]) : null;
                        $dewaPremiseNumber = isset($row[4]) ? trim($row[4]) : null;

                        if (empty($unitNumber)) {
                            $results['errors'][] = "Row {$results['total']}: Unit number is required";
                            $results['skipped']++;
                            continue;
                        }

                        // Check if unit already exists
                        $existingUnit = Unit::where('property_id', $request->property_id)
                            ->where('unit', $unitNumber)
                            ->first();

                        if ($existingUnit) {
                            $results['errors'][] = "Row {$results['total']}: Unit '{$unitNumber}' already exists";
                            $results['skipped']++;
                            continue;
                        }

                        // Create unit
                        Unit::create([
                            'property_id' => $request->property_id,
                            'unit' => $unitNumber,
                            'floor' => $floor,
                            'building' => $building,
                            'square_footage' => $squareFootage,
                            'dewa_premise_number' => $dewaPremiseNumber,
                            'status' => 'unclaimed'
                        ]);

                        $results['created']++;
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
                    // Skip header row
                    array_shift($rows);
                    
                    foreach ($rows as $row) {
                        $results['total']++;
                        
                        $unitNumber = trim($row[0]);
                        $floor = isset($row[1]) ? trim($row[1]) : null;
                        $building = isset($row[2]) ? trim($row[2]) : null;
                        $squareFootage = isset($row[3]) ? trim($row[3]) : null;
                        $dewaPremiseNumber = isset($row[4]) ? trim($row[4]) : null;

                        if (empty($unitNumber)) {
                            $results['errors'][] = "Row {$results['total']}: Unit number is required";
                            $results['skipped']++;
                            continue;
                        }

                        // Check if unit already exists
                        $existingUnit = Unit::where('property_id', $request->property_id)
                            ->where('unit', $unitNumber)
                            ->first();

                        if ($existingUnit) {
                            $results['errors'][] = "Row {$results['total']}: Unit '{$unitNumber}' already exists";
                            $results['skipped']++;
                            continue;
                        }

                        // Create unit
                        Unit::create([
                            'property_id' => $request->property_id,
                            'unit' => $unitNumber,
                            'floor' => $floor,
                            'building' => $building,
                            'square_footage' => $squareFootage,
                            'dewa_premise_number' => $dewaPremiseNumber,
                            'status' => 'unclaimed'
                        ]);

                        $results['created']++;
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
                    'message' => "Bulk upload completed. {$results['created']} units created, {$results['skipped']} skipped.",
                    'results' => $results
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to bulk upload units', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk upload units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a unit.
     */
    public function update(Request $request, Unit $unit)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit' => 'sometimes|string|max:255',
                'floor' => 'nullable|string|max:50',
                'building' => 'nullable|string|max:100',
                'square_footage' => 'nullable|numeric|min:0',
                'dewa_premise_number' => 'nullable|string|max:255',
                'status' => 'sometimes|in:unclaimed,claimed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit->update($request->only([
                'unit', 'floor', 'building', 'square_footage', 'dewa_premise_number', 'status'
            ]));

            $unit->load(['property', 'users']);

            return response()->json([
                'success' => true,
                'message' => 'Unit updated successfully',
                'unit' => $unit
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update unit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'unit_id' => $unit->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a unit.
     */
    public function destroy(Unit $unit)
    {
        try {
            // Check if unit has users
            if ($unit->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete unit with assigned users'
                ], 409);
            }

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete unit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'unit_id' => $unit->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update unit payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'payment_status' => 'required|in:pending,partial,fully_paid',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit->payment_status = $request->payment_status;
            
            if ($request->payment_status === 'fully_paid') {
                $unit->payment_date = now();
            }
            
            $unit->save();

            // Create remark for payment status update
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'Payment status updated to: ' . $request->payment_status,
                'type' => 'payment_update',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            // Handle receipt upload if provided
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = 'receipt_' . time() . '_' . $file->getClientOriginalName();
                
                // Create folder structure: project_name/unit_no/
                $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
                $path = $file->storeAs($folderPath, $filename, 'public');

                $unit->attachments()->create([
                    'filename' => $filename,
                    'type' => 'payment_proof',
                ]);

                // Create remark for payment proof upload
                $unit->remarks()->create([
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i:s'),
                    'event' => 'Payment proof uploaded',
                    'type' => 'document_upload',
                    'admin_name' => $request->user()->full_name ?? 'System',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'unit' => $unit->fresh(['attachments', 'users', 'remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update payment status', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a remark to a unit
     */
    public function addRemark(Request $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'remark' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $adminName = $request->user()->name ?? 'Admin';
            
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => $request->remark,
                'type' => 'admin_note',
                'admin_name' => $adminName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Remark added successfully',
                'unit' => $unit->fresh(['remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to add remark', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add remark',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send SOA email to all unit owners
     */
    public function sendSOAEmail(Request $request, $id)
    {
        try {
            $unit = Unit::with('users')->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'soa' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Upload SOA file
            $file = $request->file('soa');
            $filename = $file->getClientOriginalName();
            
            // Create folder structure: project_name/unit_no/
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $path = $file->storeAs($folderPath, $filename, 'public');

            $unit->attachments()->create([
                'filename' => $filename,
                'type' => 'soa',
            ]);

            // Add remark about SOA upload
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'SOA document uploaded',
                'type' => 'system',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            // Note: Email sending disabled as per previous requirements
            // In the future, send email to all unit->users here

            return response()->json([
                'success' => true,
                'message' => 'SOA uploaded successfully',
                'unit' => $unit->fresh(['attachments', 'remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to send SOA email', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SOA email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get handover status for unit
     */
    public function getHandoverStatus($id)
    {
        try {
            $unit = Unit::with('attachments')->findOrFail($id);

            $requirements = [
                ['type' => 'payment_proof', 'label' => 'Payment Proof', 'required' => true],
                ['type' => 'ac_connection', 'label' => 'AC Connection', 'required' => true],
                ['type' => 'dewa_connection', 'label' => 'DEWA Connection', 'required' => true],
                ['type' => 'service_charge_ack', 'label' => 'Service Charge Acknowledgement', 'required' => true],
                ['type' => 'developer_noc', 'label' => 'Developer NOC', 'required' => true],
            ];

            if ($unit->has_mortgage) {
                $requirements[] = ['type' => 'bank_noc', 'label' => 'Bank NOC', 'required' => true];
            }

            $requirementsWithStatus = array_map(function($req) use ($unit) {
                $uploaded = $unit->attachments->contains('type', $req['type']);
                return array_merge($req, ['uploaded' => $uploaded]);
            }, $requirements);

            return response()->json([
                'success' => true,
                'handover_ready' => $unit->handover_ready,
                'has_mortgage' => $unit->has_mortgage,
                'requirements' => $requirementsWithStatus
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get handover status', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get handover status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update unit mortgage status
     */
    public function updateMortgageStatus(Request $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'has_mortgage' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit->has_mortgage = $request->has_mortgage;
            $unit->save();

            return response()->json([
                'success' => true,
                'message' => 'Mortgage status updated successfully',
                'unit' => $unit
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update mortgage status', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update mortgage status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send initial handover notice email to all unit owners
     */
    public function sendHandoverEmail(Request $request, $id)
    {
        try {
            $unit = Unit::with(['users', 'attachments', 'property'])->findOrFail($id);

            // Check if SOA exists
            $soaAttachments = $unit->attachments->where('type', 'soa');
            if ($soaAttachments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please upload SOA before sending handover notice'
                ], 422);
            }

            // Get all owner emails
            $recipients = $unit->users->pluck('email')->toArray();
            
            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No owners found for this unit'
                ], 422);
            }

            // Get primary owner (or first owner)
            $primaryOwner = $unit->users->firstWhere('pivot.is_primary', true) ?? $unit->users->first();
            $firstName = explode(' ', $primaryOwner->full_name)[0];
            
            // Get SOA URL (using the first SOA attachment)
            $firstSOA = $soaAttachments->first();
            $soaUrl = $firstSOA ? $firstSOA->full_url : '#';

            // Send email to all owners with SOA attachments
            \Mail::send('emails.handover-notice', [
                'firstName' => $firstName,
                'soaUrl' => $soaUrl,
                'unit' => $unit,
                'property' => $unit->property,
            ], function($message) use ($recipients, $unit, $soaAttachments) {
                $message->to($recipients)
                    ->subject('Handover Notice - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                
                // Attach all SOA files
                foreach ($soaAttachments as $attachment) {
                    $filePath = storage_path('app/public/attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $attachment->filename);
                    if (file_exists($filePath)) {
                        $message->attach($filePath, [
                            'as' => $attachment->filename,
                            'mime' => 'application/pdf'
                        ]);
                    }
                }

                // Attach handover notice PDFs from project-specific folder
                $projectSlug = strtolower(str_replace(' ', '-', $unit->property->project_name));
                $handoverDocumentsPath = storage_path('app/public/handover-notice-attachments/' . $projectSlug);
                
                if (is_dir($handoverDocumentsPath)) {
                    $files = glob($handoverDocumentsPath . '/*.pdf');
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            $message->attach($file, [
                                'as' => basename($file),
                                'mime' => 'application/pdf'
                            ]);
                        }
                    }
                }
            });

            // Mark handover email as sent
            $unit->handover_email_sent = true;
            $unit->handover_email_sent_at = now();
            $unit->save();

            // Add remark about handover email sent
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'Handover notice email sent to ' . count($recipients) . ' owner(s): ' . implode(', ', $recipients),
                'type' => 'email_sent',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Handover notice sent successfully to ' . count($recipients) . ' recipient(s)',
                'unit' => $unit->fresh(['remarks', 'users', 'attachments'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to send handover email', [
                'error' => $e->getMessage(),
                'unit_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send handover email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send booking link to all unit owners
     */
    public function sendBookingLink(Request $request, $id)
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($id);

            // Get all owner emails
            $recipients = $unit->users->pluck('email')->toArray();
            
            if (empty($recipients)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No owners found for this unit'
                ], 422);
            }

            // Send email to each owner with their own magic link
            foreach ($unit->users as $user) {
                // Generate magic link for this specific user and unit
                $magicLink = \App\Models\MagicLink::create([
                    'user_id' => $user->id,
                    'token' => \Illuminate\Support\Str::random(64),
                    'expires_at' => now()->addHours(72),
                ]);

                $bookingUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/booking?token=' . $magicLink->token . '&unit_id=' . $unit->id;
                $firstName = explode(' ', $user->full_name)[0];

                \Mail::send('emails.booking-link', [
                    'firstName' => $firstName,
                    'bookingUrl' => $bookingUrl,
                    'unit' => $unit,
                    'property' => $unit->property,
                ], function($message) use ($user, $unit) {
                    $message->to($user->email)
                        ->subject('Booking Platform Access - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                });
            }

            // Add remark about booking link sent
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'Booking link sent to ' . count($recipients) . ' owner(s): ' . implode(', ', $recipients),
                'type' => 'email_sent',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking link sent successfully to ' . count($recipients) . ' recipient(s)',
                'unit' => $unit->fresh(['remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to send booking link', [
                'error' => $e->getMessage(),
                'unit_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send booking link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload attachment for unit
     */
    public function uploadAttachment(Request $request, $id)
    {
        try {
            $unit = Unit::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240',
                'type' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('file');
            $filename = $request->type . '_' . time() . '_' . $file->getClientOriginalName();
            
            // Create folder structure: project_name/unit_no/
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $path = $file->storeAs($folderPath, $filename, 'public');

            $attachment = $unit->attachments()->create([
                'filename' => $filename,
                'type' => $request->type,
            ]);

            // Map attachment types to readable labels
            $typeLabels = [
                'payment_proof' => 'Payment Proof',
                'ac_connection' => 'AC Connection',
                'dewa_connection' => 'DEWA Connection',
                'service_charge_ack' => 'Service Charge Acknowledgement',
                'developer_noc' => 'Developer NOC',
                'title_deed' => 'Title Deed',
                'sale_agreement' => 'Sale Agreement',
                'poa' => 'Power of Attorney',
                'soa' => 'Statement of Account',
            ];
            $typeLabel = $typeLabels[$request->type] ?? ucwords(str_replace('_', ' ', $request->type));

            // Add remark about document upload
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => $typeLabel . ' document uploaded',
                'type' => 'document_upload',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            // Check if all handover requirements are met
            $this->checkHandoverReady($unit);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'attachment' => $attachment,
                'unit' => $unit->fresh(['attachments', 'remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to upload attachment', [
                'error' => $e->getMessage(),
                'unit_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete unit attachment
     */
    public function deleteAttachment(Request $request, $unitId, $attachmentId)
    {
        try {
            $unit = Unit::findOrFail($unitId);
            $attachment = $unit->attachments()->findOrFail($attachmentId);

            // Store attachment info for remark before deleting
            $attachmentType = $attachment->type;
            $attachmentFilename = $attachment->filename;

            // Delete file from storage
            $filePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $attachment->filename;
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $attachment->delete();

            // Map attachment types to readable labels
            $typeLabels = [
                'payment_proof' => 'Payment Proof',
                'ac_connection' => 'AC Connection',
                'dewa_connection' => 'DEWA Connection',
                'service_charge_ack' => 'Service Charge Acknowledgement',
                'developer_noc' => 'Developer NOC',
                'title_deed' => 'Title Deed',
                'sale_agreement' => 'Sale Agreement',
                'poa' => 'Power of Attorney',
                'soa' => 'Statement of Account',
            ];
            $typeLabel = $typeLabels[$attachmentType] ?? ucwords(str_replace('_', ' ', $attachmentType));

            // Add remark about document deletion
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => $typeLabel . ' document deleted',
                'type' => 'document_deletion',
                'admin_name' => $request->user()->full_name ?? 'System',
            ]);

            // Re-check handover ready status
            $this->checkHandoverReady($unit);

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully',
                'unit' => $unit->fresh(['attachments', 'remarks'])
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete attachment', [
                'error' => $e->getMessage(),
                'unit_id' => $unitId,
                'attachment_id' => $attachmentId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if all handover requirements are met and update handover_ready status
     */
    private function checkHandoverReady(Unit $unit)
    {
        $requiredTypes = [
            'payment_proof',
            'ac_connection',
            'dewa_connection',
            'service_charge_ack',
            'developer_noc'
        ];

        if ($unit->has_mortgage) {
            $requiredTypes[] = 'bank_noc';
        }

        $uploadedTypes = $unit->attachments->pluck('type')->unique()->toArray();
        $allRequirementsMet = empty(array_diff($requiredTypes, $uploadedTypes));

        $unit->handover_ready = $allRequirementsMet;
        $unit->save();
    }
}

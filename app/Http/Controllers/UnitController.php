<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Property;
use App\Models\UserAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
            $query = Unit::with([
                'property', 
                'users', 
                'attachments.unit.property',  // Load unit and property for attachment URLs
                'booking.snaggingDefects'
            ]);

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
            
            // Delete existing SOA if it exists (overwrite)
            $existingSOA = $unit->attachments()->where('type', 'soa')->first();
            if ($existingSOA) {
                Storage::disk('public')->delete($folderPath . '/' . $existingSOA->filename);
                $existingSOA->delete();
            }
            
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SOA email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk upload SOA files
     */
    public function bulkUploadSOA(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files.*' => 'required|file|mimes:pdf|max:10240',
                'unit_ids' => 'required|array',
                'unit_ids.*' => 'required|integer|exists:units,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $files = $request->file('files');
            $unitIds = $request->input('unit_ids');
            $uploadedCount = 0;
            $errors = [];

            foreach ($files as $index => $file) {
                try {
                    $unitId = $unitIds[$index] ?? null;
                    
                    if (!$unitId) {
                        $errors[] = [
                            'file' => $file->getClientOriginalName(),
                            'error' => 'No unit ID provided'
                        ];
                        continue;
                    }

                    $unit = Unit::with('property')->findOrFail($unitId);
                    $filename = $file->getClientOriginalName();
                    
                    // Create folder structure: project_name/unit_no/
                    $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
                    
                    // Delete existing SOA if it exists (overwrite)
                    $existingSOA = $unit->attachments()->where('type', 'soa')->first();
                    if ($existingSOA) {
                        Storage::disk('public')->delete($folderPath . '/' . $existingSOA->filename);
                        $existingSOA->delete();
                    }
                    
                    // Store with original filename
                    $file->storeAs($folderPath, $filename, 'public');

                    $unit->attachments()->create([
                        'filename' => $filename,
                        'type' => 'soa',
                    ]);

                    // Add remark about SOA upload
                    $unit->remarks()->create([
                        'date' => now()->format('Y-m-d'),
                        'time' => now()->format('H:i:s'),
                        'event' => 'SOA document uploaded via bulk upload',
                        'type' => 'system',
                        'admin_name' => $request->user()->full_name ?? 'System',
                    ]);

                    $uploadedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully uploaded {$uploadedCount} SOA file(s)",
                'uploaded_count' => $uploadedCount,
                'errors' => $errors
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk upload SOA',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload payment details from CSV file
     */
    public function uploadPaymentDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
                'property_id' => 'required|exists:properties,id',
                'with_pho' => 'nullable|in:true,false,1,0'
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
            $withPho = filter_var($request->input('with_pho', false), FILTER_VALIDATE_BOOLEAN);
            $updatedCount = 0;
            $updatedUnitIds = [];
            $errors = [];
            $skipped = [];

            DB::beginTransaction();

            try {
                if ($extension === 'csv' || $extension === 'txt') {
                    $handle = fopen($file->getRealPath(), 'r');
                    
                    // Skip header row
                    $header = fgetcsv($handle);
                    
                    while (($row = fgetcsv($handle)) !== false) {
                        try {
                            $expectedColumns = $withPho ? 9 : 8;
                            if (count($row) < $expectedColumns) {
                                $skipped[] = "Row skipped: Invalid format (expected {$expectedColumns} columns)";
                                continue;
                            }

                            $unitNumber = trim($row[0]);
                            $buyer1 = trim($row[1]);
                            $totalUnitPrice = $this->parseCurrencyValue($row[2]);
                            $dldFees = $this->parseCurrencyValue($row[3]);
                            $adminFee = $this->parseCurrencyValue($row[4]);
                            $amountToPay = $this->parseCurrencyValue($row[5]);
                            $totalAmountPaid = $this->parseCurrencyValue($row[6]);
                            
                            // PHO columns
                            $uponCompletionAmount = null;
                            $dueAfterCompletion = null;
                            $outstandingAmount = null;
                            
                            if ($withPho) {
                                $uponCompletionAmount = $this->parseCurrencyValue($row[7]);
                                $dueAfterCompletion = $this->parseCurrencyValue($row[8]);
                            } else {
                                $outstandingAmount = $this->parseCurrencyValue($row[7]);
                            }

                            if (empty($unitNumber)) {
                                $skipped[] = "Row skipped: Empty unit number";
                                continue;
                            }

                            // Find unit by unit number and property
                            $unit = Unit::where('property_id', $request->property_id)
                                ->where('unit', $unitNumber)
                                ->first();

                            if (!$unit) {
                                $skipped[] = "Unit {$unitNumber}: Not found";
                                continue;
                            }

                            // Update payment details
                            $updateData = [
                                'total_unit_price' => $totalUnitPrice,
                                'dld_fees' => $dldFees,
                                'admin_fee' => $adminFee,
                                'amount_to_pay' => $amountToPay,
                                'total_amount_paid' => $totalAmountPaid,
                                'has_pho' => $withPho,
                            ];
                            
                            if ($withPho) {
                                $updateData['upon_completion_amount'] = $uponCompletionAmount;
                                $updateData['due_after_completion'] = $dueAfterCompletion;
                                $updateData['outstanding_amount'] = null;
                            } else {
                                $updateData['outstanding_amount'] = $outstandingAmount;
                                $updateData['upon_completion_amount'] = null;
                                $updateData['due_after_completion'] = null;
                            }
                            
                            $unit->update($updateData);

                            $updatedCount++;
                            $updatedUnitIds[] = $unit->id;
                        } catch (\Exception $e) {
                            $errors[] = "Unit {$unitNumber}: " . $e->getMessage();
                        }
                    }
                    
                    fclose($handle);
                } elseif ($extension === 'xlsx') {
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
                        try {
                            $expectedColumns = $withPho ? 9 : 8;
                            if (count($row) < $expectedColumns) {
                                $skipped[] = "Row skipped: Invalid format (expected {$expectedColumns} columns)";
                                continue;
                            }

                            $unitNumber = trim($row[0]);
                            $buyer1 = trim($row[1]);
                            $totalUnitPrice = $this->parseCurrencyValue($row[2]);
                            $dldFees = $this->parseCurrencyValue($row[3]);
                            $adminFee = $this->parseCurrencyValue($row[4]);
                            $amountToPay = $this->parseCurrencyValue($row[5]);
                            $totalAmountPaid = $this->parseCurrencyValue($row[6]);
                            
                            // PHO columns
                            $uponCompletionAmount = null;
                            $dueAfterCompletion = null;
                            $outstandingAmount = null;
                            
                            if ($withPho) {
                                $uponCompletionAmount = $this->parseCurrencyValue($row[7]);
                                $dueAfterCompletion = $this->parseCurrencyValue($row[8]);
                            } else {
                                $outstandingAmount = $this->parseCurrencyValue($row[7]);
                            }

                            if (empty($unitNumber)) {
                                $skipped[] = "Row skipped: Empty unit number";
                                continue;
                            }

                            // Find unit
                            $unit = Unit::where('property_id', $request->property_id)
                                ->where('unit', $unitNumber)
                                ->first();

                            if (!$unit) {
                                $skipped[] = "Unit {$unitNumber}: Not found";
                                continue;
                            }

                            // Update payment details
                            $updateData = [
                                'total_unit_price' => $totalUnitPrice,
                                'dld_fees' => $dldFees,
                                'admin_fee' => $adminFee,
                                'amount_to_pay' => $amountToPay,
                                'total_amount_paid' => $totalAmountPaid,
                                'has_pho' => $withPho,
                            ];
                            
                            if ($withPho) {
                                $updateData['upon_completion_amount'] = $uponCompletionAmount;
                                $updateData['due_after_completion'] = $dueAfterCompletion;
                                $updateData['outstanding_amount'] = null;
                            } else {
                                $updateData['outstanding_amount'] = $outstandingAmount;
                                $updateData['upon_completion_amount'] = null;
                                $updateData['due_after_completion'] = null;
                            }
                            
                            $unit->update($updateData);

                            $updatedCount++;
                            $updatedUnitIds[] = $unit->id;
                        } catch (\Exception $e) {
                            $errors[] = "Unit {$unitNumber}: " . $e->getMessage();
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Successfully updated payment details for {$updatedCount} unit(s)",
                    'updated_count' => $updatedCount,
                    'updated_unit_ids' => $updatedUnitIds,
                    'skipped' => $skipped,
                    'errors' => $errors
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment details for a single unit and generate SOA
     */
    /**
     * Update payment details only without generating SOA
     */
    public function updatePaymentDetailsOnly(Request $request, $id)
    {
        $request->validate([
            'total_unit_price' => 'nullable|numeric',
            'dld_fees' => 'nullable|numeric',
            'admin_fee' => 'nullable|numeric',
            'amount_to_pay' => 'nullable|numeric',
            'total_amount_paid' => 'nullable|numeric',
            'outstanding_amount' => 'nullable|numeric',
            'upon_completion_amount' => 'nullable|numeric',
            'due_after_completion' => 'nullable|numeric',
            'has_pho' => 'nullable|boolean',
        ]);

        try {
            $unit = Unit::with(['property', 'users'])->findOrFail($id);

            // Update payment details
            $unit->update([
                'total_unit_price' => $request->total_unit_price,
                'dld_fees' => $request->dld_fees,
                'admin_fee' => $request->admin_fee,
                'amount_to_pay' => $request->amount_to_pay,
                'total_amount_paid' => $request->total_amount_paid,
                'outstanding_amount' => $request->outstanding_amount,
                'upon_completion_amount' => $request->upon_completion_amount,
                'due_after_completion' => $request->due_after_completion,
                'has_pho' => $request->has_pho ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment details updated successfully',
                'unit' => $unit->fresh(['property', 'users', 'attachments']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSingleUnitPaymentDetails(Request $request, $id)
    {
        $request->validate([
            'total_unit_price' => 'nullable|numeric',
            'dld_fees' => 'nullable|numeric',
            'admin_fee' => 'nullable|numeric',
            'amount_to_pay' => 'nullable|numeric',
            'total_amount_paid' => 'nullable|numeric',
            'outstanding_amount' => 'nullable|numeric',
            'upon_completion_amount' => 'nullable|numeric',
            'due_after_completion' => 'nullable|numeric',
            'has_pho' => 'nullable|boolean',
        ]);

        try {
            $unit = Unit::with(['property', 'users'])->findOrFail($id);

            // Update payment details
            $unit->update([
                'total_unit_price' => $request->total_unit_price,
                'dld_fees' => $request->dld_fees,
                'admin_fee' => $request->admin_fee,
                'amount_to_pay' => $request->amount_to_pay,
                'total_amount_paid' => $request->total_amount_paid,
                'outstanding_amount' => $request->outstanding_amount,
                'upon_completion_amount' => $request->upon_completion_amount,
                'due_after_completion' => $request->due_after_completion,
                'has_pho' => $request->has_pho ?? false,
            ]);

            // Generate SOA
            try {
                // Delete all existing SOA attachments for this unit
                $existingSOAs = $unit->attachments()->where('type', 'soa')->get();
                foreach ($existingSOAs as $existingSOA) {
                    // Delete file from storage (same structure as bulk generation)
                    $propertyFolder = $unit->property->project_name;
                    $unitFolder = $unit->unit;
                    $filePath = "attachments/{$propertyFolder}/{$unitFolder}/{$existingSOA->filename}";
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                    // Delete database record
                    $existingSOA->delete();
                }

                // Load logos using Storage facade (same as bulk generation job)
                $vieraLogo = '';
                $vantageLogo = '';
                
                if (Storage::disk('public')->exists('letterheads/viera-black.png')) {
                    $vieraLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/viera-black.png'));
                }
                
                if (Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                    $vantageLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/vantage-black.png'));
                }

                // Generate PDF (same as bulk generation job)
                $owners = $unit->users;
                $property = $unit->property;

                $pdf = \PDF::loadView('pdfs.soa', [
                    'unit' => $unit,
                    'owners' => $owners,
                    'property' => $property,
                    'logos' => [
                        'left' => $vieraLogo,
                        'right' => $vantageLogo
                    ]
                ]);

                // Save PDF with new naming convention (same structure as bulk generation)
                $filename = "{$unit->unit}-soa.pdf";
                $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
                $filepath = $folderPath . '/' . $filename;
                Storage::disk('public')->put($filepath, $pdf->output());

                // Create attachment record
                $attachment = new UserAttachment();
                $attachment->unit_id = $unit->id;
                $attachment->filename = $filename;
                $attachment->type = 'soa';
                $attachment->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment details updated and SOA generated successfully',
                    'unit' => $unit->fresh(['property', 'users', 'attachments']),
                    'attachment' => $attachment,
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment details updated but SOA generation failed',
                    'error' => $e->getMessage(),
                    'unit' => $unit->fresh(['property', 'users', 'attachments']),
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment details',
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

            // Buyer requirements
            $buyerRequirements = [
                ['type' => 'payment_proof', 'label' => '100% SOA Receipt', 'required' => true],
                ['type' => 'ac_connection', 'label' => 'AC Connection', 'required' => true],
                ['type' => 'dewa_connection', 'label' => 'DEWA Connection', 'required' => true],
                ['type' => 'service_charge_ack_buyer', 'label' => 'Service Charge Acknowledgement (Signed by Buyer)', 'required' => true],
                ['type' => 'finance_clearance', 'label' => 'Finance Clearance', 'required' => true],
            ];

            if ($unit->has_mortgage) {
                $buyerRequirements[] = ['type' => 'bank_noc', 'label' => 'Bank NOC', 'required' => true];
            }

            // Developer requirements
            $developerRequirements = [
                ['type' => 'developer_noc_signed', 'label' => 'Developer NOC (Signed by Developer)', 'required' => true],
            ];

            $buyerRequirementsWithStatus = array_map(function($req) use ($unit) {
                $uploaded = $unit->attachments->contains('type', $req['type']);
                return array_merge($req, ['uploaded' => $uploaded]);
            }, $buyerRequirements);

            $developerRequirementsWithStatus = array_map(function($req) use ($unit) {
                $uploaded = $unit->attachments->contains('type', $req['type']);
                return array_merge($req, ['uploaded' => $uploaded]);
            }, $developerRequirements);

            // Check if all requirements are met
            $buyerReady = collect($buyerRequirementsWithStatus)->every(fn($req) => $req['uploaded']);
            $developerReady = collect($developerRequirementsWithStatus)->every(fn($req) => $req['uploaded']);
            $handoverReady = $buyerReady && $developerReady;

            return response()->json([
                'success' => true,
                'handover_ready' => $handoverReady,
                'buyer_ready' => $buyerReady,
                'developer_ready' => $developerReady,
                'has_mortgage' => $unit->has_mortgage,
                'buyer_requirements' => $buyerRequirementsWithStatus,
                'developer_requirements' => $developerRequirementsWithStatus
            ], 200);
        } catch (\Exception $e) {

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

            // Recheck handover status since mortgage affects required documents
            $this->checkHandoverReady($unit);

            return response()->json([
                'success' => true,
                'message' => 'Mortgage status updated successfully',
                'unit' => $unit->fresh()
            ], 200);
        } catch (\Exception $e) {

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
    public function sendHandoverEmail(Request $request, $unit)
    {
        try {
            $unit = Unit::with(['users', 'attachments', 'property'])->findOrFail($unit);

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
            if ($firstSOA && $firstSOA->unit) {
                $folderPath = 'attachments/' . $firstSOA->unit->property->project_name . '/' . $firstSOA->unit->unit;
                $soaUrl = url('storage/' . $folderPath . '/' . $firstSOA->filename);
            } else {
                $soaUrl = '#';
            }

            // Generate Service Charge Acknowledgement PDF
            $serviceChargeFilename = 'Service_Charge_Undertaking_Letter_Unit_' . $unit->unit . '.pdf';
            $serviceChargeStoragePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $serviceChargeFilename;
            
            // Only generate if it doesn't already exist
            if (\Storage::disk('public')->exists($serviceChargeStoragePath)) {
                $serviceChargePdfContent = \Storage::disk('public')->get($serviceChargeStoragePath);
            } else {
                $owners = $unit->users;
                $date = $unit->handover_email_sent_at 
                    ? \Carbon\Carbon::parse($unit->handover_email_sent_at)->format('F d, Y')
                    : now()->format('F d, Y');
                
                $serviceChargePdf = \PDF::loadView('pdfs.service-charge-acknowledgement', [
                    'unit' => $unit,
                    'owners' => $owners,
                    'date' => $date
                ]);
                $serviceChargePdfContent = $serviceChargePdf->output();
                
                // Save to storage
                \Storage::disk('public')->put($serviceChargeStoragePath, $serviceChargePdfContent);
            }

            // Generate Utilities Registration Guide PDF with logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $utilitiesGuidePdf = \PDF::loadView('utilities-registration-guide', [
                'dewaPremiseNumber' => $unit->dewa_premise_number ?? 'N/A',
                'logos' => [
                    'left' => $vieraLogo,
                    'right' => $vantageLogo,
                ]
            ]);
            $utilitiesGuidePdfContent = $utilitiesGuidePdf->output();

            // Save utilities guide PDF to storage using Laravel Storage (overwrite if exists)
            $utilitiesGuideFilename = 'Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf';
            $storagePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $utilitiesGuideFilename;
            \Storage::disk('public')->put($storagePath, $utilitiesGuidePdfContent);

            // Send email to all owners with SOA attachments
            \Mail::send('emails.handover-notice', [
                'firstName' => $firstName,
                'soaUrl' => $soaUrl,
                'unit' => $unit,
                'property' => $unit->property,
            ], function($message) use ($recipients, $unit, $soaAttachments, $serviceChargePdfContent, $storagePath) {
                $message->to($recipients)
                    ->subject('Handover Notice - Unit ' . $unit->unit . ', ' . $unit->property->project_name);
                
                // Request read and delivery receipts
                $message->getHeaders()->addTextHeader('Disposition-Notification-To', config('mail.from.address'));
                $message->getHeaders()->addTextHeader('Return-Receipt-To', config('mail.from.address'));
                
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

                // Attach Service Charge Acknowledgement PDF
                $message->attachData($serviceChargePdfContent, 'Service_Charge_Acknowledgement_Unit_' . $unit->unit . '.pdf', [
                    'mime' => 'application/pdf'
                ]);

                // Attach Utilities Registration Guide PDF from storage
                $utilitiesGuidePath = storage_path('app/public/' . $storagePath);
                if (file_exists($utilitiesGuidePath)) {
                    $message->attach($utilitiesGuidePath, [
                        'as' => 'Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf',
                        'mime' => 'application/pdf'
                    ]);
                }

                // Attach Escrow Account PDF
                $escrowPath = storage_path('app/public/handover-notice-attachments/viera-residences/Viera Residences - Escrow Acc.pdf');
                \Log::info('Escrow path check', ['path' => $escrowPath, 'exists' => file_exists($escrowPath)]);
                if (file_exists($escrowPath)) {
                    $message->attach($escrowPath, [
                        'as' => 'Viera Residences - Escrow Acc.pdf',
                        'mime' => 'application/pdf'
                    ]);
                }

                // Attach RERA Inspection Report PDF
                $inspectionPath = storage_path('app/public/handover-notice-attachments/viera-residences/RERA_inspection_report.pdf');
                \Log::info('Inspection path check', ['path' => $inspectionPath, 'exists' => file_exists($inspectionPath)]);
                if (file_exists($inspectionPath)) {
                    $message->attach($inspectionPath, [
                        'as' => 'RERA_inspection_report.pdf',
                        'mime' => 'application/pdf'
                    ]);
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to send handover email: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk send SOA payment reminder emails (queued)
     */
    public function bulkSendSOAEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_ids' => 'required|array',
                'unit_ids.*' => 'required|integer|exists:units,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unitIds = $request->input('unit_ids');
            $adminName = $request->user()->full_name ?? 'System';
            $queuedCount = 0;
            $skipped = [];
            $validUnitIds = [];

            foreach ($unitIds as $unitId) {
                try {
                    // Quick validation before queuing
                    $unit = Unit::with(['users', 'attachments'])->findOrFail($unitId);
                    
                    // Check if has SOA
                    $hasSOA = $unit->attachments->where('type', 'soa')->isNotEmpty();
                    if (!$hasSOA) {
                        $skipped[] = [
                            'unit_id' => $unitId,
                            'unit' => $unit->unit,
                            'reason' => 'No SOA uploaded'
                        ];
                        continue;
                    }

                    // Check if has owners
                    if ($unit->users->isEmpty()) {
                        $skipped[] = [
                            'unit_id' => $unitId,
                            'unit' => $unit->unit,
                            'reason' => 'No owners assigned'
                        ];
                        continue;
                    }

                    $validUnitIds[] = $unitId;
                    $queuedCount++;

                } catch (\Exception $e) {
                    $skipped[] = [
                        'unit_id' => $unitId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Create batch tracking record (reuse HandoverEmailBatch model)
            $batchId = \Illuminate\Support\Str::uuid()->toString();
            $batch = \App\Models\HandoverEmailBatch::create([
                'batch_id' => $batchId,
                'total_emails' => $queuedCount,
                'sent_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'unit_ids' => $validUnitIds,
                'initiated_by' => $adminName,
                'started_at' => now()
            ]);

            // Dispatch jobs with batch ID
            foreach ($validUnitIds as $unitId) {
                \App\Jobs\SendSOAEmailJob::dispatch($unitId, $adminName, $batchId);
            }

            $message = "Queued {$queuedCount} SOA email(s) for sending.";
            if (count($skipped) > 0) {
                $message .= " Skipped " . count($skipped) . " unit(s).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'queued_count' => $queuedCount,
                'skipped' => $skipped,
                'batch_id' => $batchId
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue SOA emails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk send handover emails (queued)
     */
    public function bulkSendHandoverEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_ids' => 'required|array',
                'unit_ids.*' => 'required|integer|exists:units,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $unitIds = $request->input('unit_ids');
            $adminName = $request->user()->full_name ?? 'System';
            $queuedCount = 0;
            $skipped = [];
            $validUnitIds = [];

            foreach ($unitIds as $unitId) {
                try {
                    // Quick validation before queuing
                    $unit = Unit::with(['users', 'attachments'])->findOrFail($unitId);
                    
                    // Check if has SOA
                    $hasSOA = $unit->attachments->where('type', 'soa')->isNotEmpty();
                    if (!$hasSOA) {
                        $skipped[] = [
                            'unit_id' => $unitId,
                            'unit' => $unit->unit,
                            'reason' => 'No SOA uploaded'
                        ];
                        continue;
                    }

                    // Check if has owners
                    if ($unit->users->isEmpty()) {
                        $skipped[] = [
                            'unit_id' => $unitId,
                            'unit' => $unit->unit,
                            'reason' => 'No owners assigned'
                        ];
                        continue;
                    }

                    $validUnitIds[] = $unitId;
                    $queuedCount++;

                } catch (\Exception $e) {
                    $skipped[] = [
                        'unit_id' => $unitId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Create batch tracking record
            $batchId = \Illuminate\Support\Str::uuid()->toString();
            $batch = \App\Models\HandoverEmailBatch::create([
                'batch_id' => $batchId,
                'total_emails' => $queuedCount,
                'sent_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'unit_ids' => $validUnitIds,
                'initiated_by' => $adminName,
                'started_at' => now()
            ]);

            // Dispatch jobs with batch ID
            foreach ($validUnitIds as $unitId) {
                \App\Jobs\SendHandoverEmailJob::dispatch($unitId, $adminName, $batchId);
            }

            $message = "Queued {$queuedCount} handover email(s) for sending.";
            if (count($skipped) > 0) {
                $message .= " Skipped " . count($skipped) . " unit(s).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'queued_count' => $queuedCount,
                'skipped' => $skipped,
                'batch_id' => $batchId
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue handover emails',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get handover email batch progress
     */
    public function getHandoverEmailProgress($batchId)
    {
        try {
            $batch = \App\Models\HandoverEmailBatch::where('batch_id', $batchId)->first();

            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }

            // Get failed units details
            $failedUnits = [];
            if ($batch->failed_unit_ids && count($batch->failed_unit_ids) > 0) {
                $failedUnits = Unit::whereIn('id', $batch->failed_unit_ids)
                    ->with(['users', 'property'])
                    ->get()
                    ->map(function($unit) {
                        return [
                            'id' => $unit->id,
                            'unit_number' => $unit->unit,
                            'property' => $unit->property->project_name ?? 'N/A',
                            'owners' => $unit->users->pluck('full_name')->toArray()
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'batch' => [
                    'batch_id' => $batch->batch_id,
                    'total_emails' => $batch->total_emails,
                    'sent_count' => $batch->sent_count,
                    'failed_count' => $batch->failed_count,
                    'status' => $batch->status,
                    'progress_percentage' => $batch->getProgressPercentage(),
                    'started_at' => $batch->started_at,
                    'completed_at' => $batch->completed_at,
                    'failed_units' => $failedUnits
                ]
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to get batch progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check SOA generation status for all units
     */
    public function checkSOAStatus(Request $request)
    {
        try {
            $specificUnitIds = $request->input('unit_ids');
            
            // Build base query for units with owners
            $query = Unit::whereHas('users');
            
            // If specific unit IDs provided, filter by them
            if ($specificUnitIds && is_array($specificUnitIds) && count($specificUnitIds) > 0) {
                $query->whereIn('id', $specificUnitIds);
            }
            
            $totalUnits = $query->count();
            
            // Count units with SOA
            $withSOAQuery = clone $query;
            $unitsWithSOA = $withSOAQuery->whereHas('attachments', function ($q) {
                $q->where('type', 'soa');
            })->count();
            
            $unitsWithoutSOA = $totalUnits - $unitsWithSOA;

            return response()->json([
                'success' => true,
                'total_units' => $totalUnits,
                'units_with_soa' => $unitsWithSOA,
                'units_without_soa' => $unitsWithoutSOA,
                'all_generated' => $unitsWithoutSOA === 0,
                'filtered_by_upload' => !empty($specificUnitIds)
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to check SOA status'
            ], 500);
        }
    }

    /**
     * Bulk generate SOA for units without SOA
     */
    public function bulkGenerateSOA(Request $request)
    {
        \Log::info("=== BULK SOA GENERATION STARTED v1.0.39 ===", [
            'regenerate' => $request->input('regenerate', false),
            'with_pho' => $request->input('with_pho', false),
            'unit_ids' => $request->input('unit_ids'),
            'unit_ids_count' => is_array($request->input('unit_ids')) ? count($request->input('unit_ids')) : 0,
            'user' => $request->user()->full_name ?? 'Unknown'
        ]);
        
        try {
            $regenerate = $request->input('regenerate', false);
            $withPho = $request->input('with_pho', false);
            $specificUnitIds = $request->input('unit_ids'); // Array of unit IDs from frontend
            
            if ($regenerate) {
                // Get units for regeneration
                if ($specificUnitIds && is_array($specificUnitIds) && count($specificUnitIds) > 0) {
                    // Regenerate specific units (can be from CSV upload or from existing payment details)
                    $units = Unit::with(['users', 'attachments'])
                        ->whereIn('id', $specificUnitIds)
                        ->whereHas('users')
                        ->whereNotNull('total_unit_price') // Only units with payment details
                        ->get();
                    
                    \Log::info("Regenerating specific units with existing payment details", [
                        'requested_count' => count($specificUnitIds),
                        'units_found' => $units->count()
                    ]);
                    
                    if ($units->isEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No units found with payment details to regenerate',
                            'queued_count' => 0
                        ], 400);
                    }
                } else {
                    // No specific units provided
                    return response()->json([
                        'success' => false,
                        'message' => 'Please specify which units to regenerate',
                        'queued_count' => 0
                    ], 400);
                }

                // Delete all existing SOAs
                foreach ($units as $unit) {
                    $existingSOAs = $unit->attachments()->where('type', 'soa')->get();
                    foreach ($existingSOAs as $soa) {
                        // Delete file from storage
                        $propertyFolder = $unit->property->project_name;
                        $unitFolder = $unit->unit;
                        $filePath = "public/attachments/{$propertyFolder}/{$unitFolder}/{$soa->filename}";
                        if (Storage::exists($filePath)) {
                            Storage::delete($filePath);
                        }
                        // Delete database record
                        $soa->delete();
                    }
                }

                $unitIds = $units->pluck('id')->toArray();
            } else {
                // Get units without SOA
                // ONLY process specific units if unit_ids provided
                // This prevents accidentally generating SOAs for ALL units
                if ($specificUnitIds && is_array($specificUnitIds) && count($specificUnitIds) > 0) {
                    // Generate SOA only for specific units from CSV that don't have SOA
                    $unitsWithoutSOA = Unit::with(['users', 'attachments'])
                        ->whereIn('id', $specificUnitIds)
                        ->whereDoesntHave('attachments', function ($query) {
                            $query->where('type', 'soa');
                        })
                        ->whereHas('users') // Only units with owners
                        ->get();
                    
                    \Log::info("Processing specific units from CSV", [
                        'requested_count' => count($specificUnitIds),
                        'units_without_soa' => $unitsWithoutSOA->count()
                    ]);
                } else {
                    // No specific units provided - return error to prevent bulk generation
                    return response()->json([
                        'success' => false,
                        'message' => 'Please upload a CSV file with unit payment details first',
                        'queued_count' => 0
                    ], 400);
                }

                if ($unitsWithoutSOA->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'All specified units already have SOA',
                        'queued_count' => 0
                    ], 200);
                }

                $unitIds = $unitsWithoutSOA->pluck('id')->toArray();
            }

            $adminName = $request->user()->full_name ?? 'System';

            \Log::info("Creating batch for SOA generation", [
                'unit_count' => count($unitIds),
                'admin_name' => $adminName
            ]);

            // Create batch tracking record
            $batchId = \Illuminate\Support\Str::uuid()->toString();
            $batch = \App\Models\SoaGenerationBatch::create([
                'batch_id' => $batchId,
                'total_soas' => count($unitIds),
                'generated_count' => 0,
                'failed_count' => 0,
                'status' => 'processing',
                'unit_ids' => $unitIds,
                'initiated_by' => $adminName,
                'started_at' => now()
            ]);

            \Log::info("Batch created, dispatching jobs", [
                'batch_id' => $batchId,
                'total_jobs' => count($unitIds)
            ]);

            // Dispatch jobs
            foreach ($unitIds as $index => $unitId) {
                \Log::info("Dispatching SOA job", [
                    'job_number' => $index + 1,
                    'unit_id' => $unitId,
                    'batch_id' => $batchId,
                    'with_pho' => $withPho
                ]);
                \App\Jobs\GenerateSOAJob::dispatch($unitId, $batchId, $adminName, $withPho);
            }

            \Log::info("All jobs dispatched successfully", [
                'batch_id' => $batchId,
                'total_dispatched' => count($unitIds)
            ]);

            return response()->json([
                'success' => true,
                'message' => $regenerate 
                    ? "Queued {$batch->total_soas} SOA(s) for regeneration" 
                    : "Queued {$batch->total_soas} SOA(s) for generation",
                'queued_count' => $batch->total_soas,
                'batch_id' => $batchId
            ], 200);

        } catch (\Exception $e) {

            \Log::error("=== BULK SOA GENERATION FAILED ===", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to queue SOA generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SOA generation batch progress
     */
    public function getSOAGenerationProgress($batchId)
    {
        try {
            $batch = \App\Models\SoaGenerationBatch::where('batch_id', $batchId)->first();

            if (!$batch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'batch' => [
                    'batch_id' => $batch->batch_id,
                    'total_soas' => $batch->total_soas,
                    'generated_count' => $batch->generated_count,
                    'failed_count' => $batch->failed_count,
                    'status' => $batch->status,
                    'progress_percentage' => $batch->getProgressPercentage(),
                    'started_at' => $batch->started_at,
                    'completed_at' => $batch->completed_at
                ]
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to get SOA generation progress',
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

                $bookingUrl = config('app.frontend_url') . '/booking?token=' . $magicLink->token . '&unit_id=' . $unit->id;
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
        // Buyer requirements
        $buyerRequirements = [
            'payment_proof',
            'ac_connection',
            'dewa_connection',
            'service_charge_ack_buyer'
        ];

        if ($unit->has_mortgage) {
            $buyerRequirements[] = 'bank_noc';
        }

        // Developer requirements
        $developerRequirements = [
            'developer_noc_signed'
        ];

        // Get all uploaded attachment types
        $uploadedTypes = $unit->attachments->pluck('type')->unique()->toArray();
        
        // Check if all buyer requirements are met
        $buyerReady = empty(array_diff($buyerRequirements, $uploadedTypes));
        
        // Check if all developer requirements are met
        $developerReady = empty(array_diff($developerRequirements, $uploadedTypes));
        
        // Handover is ready when both buyer and developer requirements are met
        $allRequirementsMet = $buyerReady && $developerReady;

        $unit->handover_ready = $allRequirementsMet;
        $unit->save();
        
    }

    /**
     * Manually validate and update handover requirements status
     */
    public function validateHandoverRequirements(Request $request, $id)
    {
        try {
            $unit = Unit::with(['attachments'])->findOrFail($id);
            
            // Check and update handover status
            $this->checkHandoverReady($unit);
            
            // Get fresh unit data with updated status
            $unit = $unit->fresh(['attachments', 'remarks']);
            
            return response()->json([
                'success' => true,
                'message' => 'Handover requirements validated',
                'handover_ready' => $unit->handover_ready,
                'unit' => $unit
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate handover requirements',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Service Charge Acknowledgement PDF for a unit
     */
    public function generateServiceChargeAcknowledgement($id)
    {
        try {
            $unit = Unit::with(['property', 'users'])->findOrFail($id);
            
            // Get all owners
            $owners = $unit->users;
            
            if ($owners->isEmpty()) {
                return response()->json(['message' => 'No owners found for this unit'], 400);
            }

            // Determine the date: use handover_email_sent_at from any owner if available, otherwise current date
            $handoverDate = null;
            foreach ($owners as $owner) {
                if ($owner->handover_email_sent_at) {
                    $handoverDate = \Carbon\Carbon::parse($owner->handover_email_sent_at)->format('d/m/Y');
                    break;
                }
            }
            
            if (!$handoverDate) {
                $handoverDate = now()->format('d/m/Y');
            }

            // Generate PDF
            $pdf = \PDF::loadView('pdfs.service-charge-acknowledgement', [
                'unit' => $unit,
                'owners' => $owners,
                'date' => $handoverDate,
            ]);

            $filename = 'Service_Charge_Acknowledgement_' . $unit->property->project_name . '_' . $unit->unit . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate document'], 500);
        }
    }

    /**
     * Generate Utilities Registration Guide PDF for a unit
     */
    public function generateUtilitiesGuide($id)
    {
        try {
            $unit = Unit::with(['property'])->findOrFail($id);

            // Get logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            // Generate PDF
            $pdf = \PDF::loadView('utilities-registration-guide', [
                'dewaPremiseNumber' => $unit->dewa_premise_number ?? 'N/A',
                'logos' => $logos,
            ]);

            $filename = 'Utilities_Registration_Guide_Unit_' . $unit->unit . '.pdf';
            
            // Save to storage first
            $storagePath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $filename;
            \Storage::disk('public')->put($storagePath, $pdf->output());
            
            // Return the file from storage
            return \Storage::disk('public')->download($storagePath, $filename);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate utilities guide'], 500);
        }
    }

    /**
     * Generate No Objection Certificate (NOC) for unit handover
     */
    public function generateNOC($id)
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($id);

            // Get all owners
            $owners = $unit->users;
            if ($owners->isEmpty()) {
                return response()->json(['message' => 'No owners found for this unit'], 404);
            }

            // Prepare data for PDF
            $buyer1_name = $owners[0]->full_name ?? 'N/A';
            $buyer2_name = isset($owners[1]) ? $owners[1]->full_name : '';

            // Get logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            // Generate PDF
            $pdf = \PDF::loadView('noc-handover-pdf', [
                'date' => now()->format('F d, Y'),
                'unit_number' => $unit->unit,
                'buyer1_name' => $buyer1_name,
                'buyer2_name' => $buyer2_name,
                'logos' => $logos,
            ]);

            $filename = 'NOC_Handover_' . $unit->property->project_name . '_Unit_' . $unit->unit . '.pdf';
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate NOC'], 500);
        }
    }

    /**
     * Generate Finance Clearance PDF
     */
    public function generateClearance(Request $request, $id)
    {
        try {
            $unit = Unit::with(['users', 'property'])->findOrFail($id);
            $admin = $request->user();

            // Validate request
            $validated = $request->validate([
                'requirement1' => 'required|boolean',
                'requirement2' => 'required|boolean',
                'requirement3' => 'required|boolean',
                'requirement4' => 'required|string|in:yes,nil',
                'requirement4_amount' => 'nullable|numeric|min:0',
                'requirement5' => 'required|string|in:yes,nil',
                'requirement5_amount' => 'nullable|numeric|min:0',
                'requirement6' => 'required|boolean',
                'requirement7' => 'required|string|in:yes,nil',
                'remarks' => 'nullable|string',
            ]);

            // Get all owners
            $owners = $unit->users;
            if ($owners->isEmpty()) {
                return response()->json(['message' => 'No owners found for this unit'], 404);
            }

            // Get primary owner
            $primaryOwner = $owners->where('pivot.is_primary', true)->first() ?? $owners->first();

            // Get logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            // Calculate payment details
            $totalUnitPrice = $unit->total_unit_price ?? 0;
            $dldFees = $unit->dld_fees ?? 0;
            $adminFee = $unit->admin_fee ?? 0;
            $totalDldAndAdmin = $dldFees + $adminFee;
            $totalAmount = $unit->amount_to_pay ?? 0;
            $totalReceived = $unit->total_amount_paid ?? 0;
            $hasPHO = $unit->has_pho ?? false;
            
            // For PHO units, calculate proper values based on purchase price and DLD+admin
            if ($hasPHO) {
                // If total_amount is 0 or not set, calculate it
                if ($totalAmount == 0) {
                    $totalAmount = $totalUnitPrice + $totalDldAndAdmin;
                }
                // Set total received to match total amount (fully paid)
                $totalReceived = $totalAmount;
                $amountPaidTowardsPurchase = $totalUnitPrice;
                $amountPaidTowardsDldAdmin = $totalDldAndAdmin;
                $excessPayment = 0;
                $percentageCompleted = 100;
            } else {
                $amountPaidTowardsPurchase = min($totalReceived, $totalUnitPrice);
                // Calculate amount paid towards DLD + Admin (after purchase price is covered)
                $amountPaidTowardsDldAdmin = max(0, min($totalReceived - $totalUnitPrice, $totalDldAndAdmin));
                $excessPayment = max(0, $totalReceived - $totalAmount);
                $percentageCompleted = $totalUnitPrice > 0 ? ($amountPaidTowardsPurchase / $totalUnitPrice * 100) : 0;
            }

            // Generate PDF
            $pdf = \PDF::loadView('pdfs.clearance', [
                'date' => now()->format('d/m/Y'),
                'unit' => $unit,
                'property' => $unit->property,
                'client_name' => $primaryOwner->full_name,
                'purchase_price' => $totalUnitPrice,
                'dld_and_admin' => $totalDldAndAdmin,
                'total_amount' => $totalAmount,
                'total_received' => $totalReceived,
                'amount_paid_towards_purchase' => $amountPaidTowardsPurchase,
                'amount_paid_towards_dld_admin' => $amountPaidTowardsDldAdmin,
                'excess_payment' => $excessPayment,
                'percentage_completed' => $percentageCompleted,
                'percentage_completed' => $percentageCompleted,
                'requirement1' => $validated['requirement1'] ? 'YES' : 'NO',
                'requirement2' => $validated['requirement2'] ? 'YES' : 'NO',
                'requirement3' => $validated['requirement3'] ? 'YES' : 'NO',
                'requirement4' => strtoupper($validated['requirement4']),
                'requirement4_amount' => $validated['requirement4_amount'] ?? null,
                'requirement5' => strtoupper($validated['requirement5']),
                'requirement5_amount' => $validated['requirement5_amount'] ?? null,
                'requirement6' => $validated['requirement6'] ? 'YES' : 'NO',
                'requirement7' => strtoupper($validated['requirement7']),
                'remarks' => $validated['remarks'] ?? '',
                'logos' => $logos,
            ]);

            $filename = 'Finance_Clearance_' . $unit->property->project_name . '_Unit_' . $unit->unit . '.pdf';
            
            // Save PDF as attachment
            $pdfContent = $pdf->output();
            $folderPath = 'attachments/' . $unit->property->project_name . '/' . $unit->unit;
            $filepath = $folderPath . '/' . $filename;
            \Storage::disk('public')->put($filepath, $pdfContent);
            
            // Create attachment record
            $attachment = $unit->attachments()->create([
                'filename' => $filename,
                'filepath' => $filepath,
                'type' => 'finance_clearance',
            ]);
            
            // Add timeline remark
            $adminName = $admin->full_name ?? 'Admin';
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => "Finance clearance generated by {$adminName}",
                'admin_name' => $adminName,
                'type' => 'clearance_generated'
            ]);
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Clearance generation failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate clearance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get preview of developer requirements email
     */
    public function previewDeveloperRequirements($id)
    {
        try {
            $unit = Unit::with(['users', 'property', 'attachments'])->findOrFail($id);

            // Check if buyer requirements are complete
            $buyerRequirementTypes = ['payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack_buyer'];
            if ($unit->has_mortgage) {
                $buyerRequirementTypes[] = 'bank_noc';
            }

            $uploadedBuyerDocs = $unit->attachments->whereIn('type', $buyerRequirementTypes);
            
            if ($uploadedBuyerDocs->count() < count($buyerRequirementTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buyer requirements are not complete'
                ], 400);
            }

            // Get primary owner
            $primaryOwner = $unit->users->first();
            if (!$primaryOwner) {
                return response()->json(['message' => 'No owner found for this unit'], 404);
            }

            return response()->json([
                'success' => true,
                'unit' => [
                    'id' => $unit->id,
                    'unit' => $unit->unit,
                    'project_name' => $unit->property->project_name,
                    'location' => $unit->property->location,
                ],
                'owner' => [
                    'full_name' => $primaryOwner->full_name,
                    'email' => $primaryOwner->email,
                    'mobile_number' => $primaryOwner->mobile_number,
                ],
                'documents' => $uploadedBuyerDocs->map(function($doc) {
                    return [
                        'id' => $doc->id,
                        'filename' => $doc->filename,
                        'type' => $doc->type,
                        'full_url' => $doc->full_url,
                    ];
                })->values()->toArray(),
                'recipient_emails' => ['inquire@vantageventures.ae', 'mtsen@evanlimpenta.com'],
                'cc_emails' => [
                    'vantage@zedcapital.ae',
                    'docs@zedcapital.ae',
                    'admin@zedcapital.ae',
                    'clientsupport@zedcapital.ae',
                    'operations@zedcapital.ae',
                    'president@zedcapital.ae',
                    'wbd@zedcapital.ae'
                ],
                'subject' => 'Handover Requirements - Unit ' . $unit->unit . ' - ' . $unit->property->project_name,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load preview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send buyer requirements to developer for approval
     */
    public function sendRequirementsToDeveloper(Request $request, $id)
    {
        try {
            $unit = Unit::with(['users', 'property', 'attachments'])->findOrFail($id);

            // Check if buyer requirements are complete
            $buyerRequirementTypes = ['payment_proof', 'ac_connection', 'dewa_connection', 'service_charge_ack_buyer', 'finance_clearance'];
            if ($unit->has_mortgage) {
                $buyerRequirementTypes[] = 'bank_noc';
            }

            $uploadedBuyerDocs = $unit->attachments->whereIn('type', $buyerRequirementTypes);
            
            if ($uploadedBuyerDocs->count() < count($buyerRequirementTypes)) {
                return response()->json(['message' => 'Buyer requirements are not complete. Missing required documents.'], 400);
            }

            // Get primary owner
            $primaryOwner = $unit->users->first();
            if (!$primaryOwner) {
                return response()->json(['message' => 'No owner found for this unit'], 404);
            }

            // Generate NOC PDF for developer to sign - get logos using Storage facade
            $vieraLogo = '';
            $vantageLogo = '';
            
            if (\Storage::disk('public')->exists('letterheads/viera-black.png')) {
                $vieraLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/viera-black.png'));
            }
            
            if (\Storage::disk('public')->exists('letterheads/vantage-black.png')) {
                $vantageLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/vantage-black.png'));
            }

            $logos = [
                'left' => $vieraLogo,
                'right' => $vantageLogo
            ];

            $nocPdf = \PDF::loadView('noc-handover-pdf', [
                'date' => now()->format('F d, Y'),
                'unit_number' => $unit->unit,
                'buyer1_name' => $primaryOwner->full_name,
                'buyer2_name' => $unit->users->count() > 1 ? $unit->users[1]->full_name : '',
                'logos' => $logos
            ]);
            $nocFilename = 'NOC_Handover_' . $unit->unit . '_' . time() . '.pdf';
            $nocPath = storage_path('app/temp/' . $nocFilename);
            
            // Create temp directory if it doesn't exist
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $nocPdf->save($nocPath);

            // Send email to developer with all documents
            \Mail::send('emails.developer-requirements', [
                'unit' => $unit,
                'owner' => $primaryOwner,
                'documents' => $uploadedBuyerDocs
            ], function ($message) use ($unit, $uploadedBuyerDocs, $nocPath, $nocFilename) {
                $message->to(['inquire@vantageventures.ae', 'mtsen@evanlimpenta.com'])
                    ->cc([
                        'vantage@zedcapital.ae',
                        'docs@zedcapital.ae',
                        'accounts@zedcapital.ae',
                        'finance@zedcapital.ae',
                        'clientsupport@zedcapital.ae',
                        'operations@zedcapital.ae',
                        'president@zedcapital.ae',
                        'wbd@zedcapital.ae'
                    ])
                    ->subject('Handover Requirements - Unit ' . $unit->unit . ' - ' . $unit->property->project_name);
                
                // Attach all buyer documents
                foreach ($uploadedBuyerDocs as $doc) {
                    try {
                        // Construct the file path - check multiple possible locations
                        $possiblePaths = [];
                        
                        // If filepath is populated, use it
                        if (!empty($doc->filepath)) {
                            $possiblePaths[] = storage_path('app/public/' . $doc->filepath);
                        }
                        
                        // Try: attachments/{property}/{unit}/{filename}
                        $possiblePaths[] = storage_path('app/public/attachments/' . $unit->property->project_name . '/' . $unit->unit . '/' . $doc->filename);
                        
                        // Try: attachments/{filename}
                        $possiblePaths[] = storage_path('app/public/attachments/' . $doc->filename);
                        
                        // Try: {filename} directly
                        $possiblePaths[] = storage_path('app/public/' . $doc->filename);
                        
                        $filePath = null;
                        foreach ($possiblePaths as $path) {
                            if (file_exists($path)) {
                                $filePath = $path;
                                break;
                            }
                        }
                        
                        // Attach the file if found
                        if ($filePath && file_exists($filePath)) {
                            $message->attach($filePath, [
                                'as' => $doc->filename,
                                'mime' => 'application/pdf',
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        // Log error but continue with other attachments
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to attach document: ' . $doc->filename,
                            'error' => $e->getMessage()
                        ], 500);
                    }
                }
                
                // Attach the NOC PDF for developer to sign
                try {
                    if (file_exists($nocPath)) {
                        $message->attach($nocPath, [
                            'as' => $nocFilename,
                            'mime' => 'application/pdf',
                        ]);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to attach NOC PDF',
                        'error' => $e->getMessage()
                    ], 500);
                }
            });
            
            // Clean up temp NOC file
            if (file_exists($nocPath)) {
                unlink($nocPath);
            }

            // Log the activity
            $unit->remarks()->create([
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i:s'),
                'event' => 'Buyer requirements sent to developer for approval',
                'type' => 'developer_email_sent',
                'admin_name' => $request->user()->full_name ?? 'System'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Requirements sent to developer successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send requirements to developer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download all SOAs as a zip file
     */
    public function downloadAllSOAs(Request $request)
    {
        try {
            // Get all units with SOA attachments
            $units = Unit::with(['property', 'attachments' => function($query) {
                $query->where('type', 'soa');
            }])->whereHas('attachments', function($query) {
                $query->where('type', 'soa');
            })->get();

            if ($units->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SOA files found'
                ], 404);
            }

            // Create a temporary zip file
            $zipFileName = 'all-soas-' . now()->format('Y-m-d-His') . '.zip';
            $zipFilePath = storage_path('app/temp/' . $zipFileName);
            
            // Create temp directory if it doesn't exist
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not create zip file'
                ], 500);
            }

            $filesAdded = 0;
            foreach ($units as $unit) {
                $soaAttachment = $unit->attachments->first();
                if ($soaAttachment) {
                    $propertyFolder = $unit->property->project_name;
                    $unitFolder = $unit->unit;
                    $filePath = "attachments/{$propertyFolder}/{$unitFolder}/{$soaAttachment->filename}";
                    
                    $fullPath = storage_path('app/public/' . $filePath);
                    
                    if (file_exists($fullPath)) {
                        // Add file to zip with project name prefix
                        $zipName = "{$propertyFolder}/{$soaAttachment->filename}";
                        $zip->addFile($fullPath, $zipName);
                        $filesAdded++;
                    }
                }
            }

            $zip->close();

            if ($filesAdded === 0) {
                unlink($zipFilePath);
                return response()->json([
                    'success' => false,
                    'message' => 'No SOA files could be found on server'
                ], 404);
            }

            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create zip file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadAllUtilitiesGuides(Request $request)
    {
        try {
            // Generate a unique batch ID
            $batchId = now()->format('YmdHis') . '-' . \Str::random(8);

            // Dispatch the job
            \App\Jobs\GenerateAllUtilitiesGuidesJob::dispatch($batchId);

            // Initialize batch status in cache
            \Cache::put("utilities_guides_batch_{$batchId}", [
                'batch_id' => $batchId,
                'status' => 'queued',
                'success_count' => 0,
                'failed_count' => 0,
                'total_count' => 0,
                'created_at' => now()->toIso8601String(),
            ], now()->addHour());

            return response()->json([
                'success' => true,
                'message' => 'Utilities guides generation started',
                'batch_id' => $batchId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start generation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUtilitiesGuidesBatchProgress($batchId)
    {
        try {
            $batchData = \Cache::get("utilities_guides_batch_{$batchId}");

            if (!$batchData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'batch' => $batchData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get batch progress',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadUtilitiesGuidesZip($batchId)
    {
        try {
            $batchData = \Cache::get("utilities_guides_batch_{$batchId}");

            if (!$batchData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }

            if ($batchData['status'] !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch is not completed yet'
                ], 400);
            }

            if (!isset($batchData['file_path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $filePath = $batchData['file_path'];
            
            if (!\Storage::disk('public')->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist'
                ], 404);
            }

            return \Storage::disk('public')->download($filePath);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse currency value from CSV (removes commas, quotes, spaces)
     */
    private function parseCurrencyValue($value)
    {
        if (empty($value) || trim($value) === '-' || trim($value) === '') {
            return null;
        }
        
        // Remove quotes, commas, and spaces
        $cleanValue = str_replace(['"', ',', ' ', "'"], '', trim($value));
        
        // Convert to float
        return is_numeric($cleanValue) ? (float) $cleanValue : null;
    }

    /**
     * Get all unit remarks (admin only)
     */
    public function getAllRemarks(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $query = \App\Models\UnitRemark::with([
                'unit.property',
                'unit.users'
            ])
            ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->input('date_from'));
            }
            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->input('date_to'));
            }
            if ($request->has('time')) {
                $query->where('time', $request->input('time'));
            }
            if ($request->has('unit')) {
                $query->whereHas('unit', function($q) use ($request) {
                    $q->where('unit', 'like', '%' . $request->input('unit') . '%');
                });
            }
            if ($request->has('project') && $request->input('project') !== 'all') {
                $query->whereHas('unit.property', function($q) use ($request) {
                    $q->where('project_name', $request->input('project'));
                });
            }

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            $remarks = $paginated->map(function ($remark) {
                return [
                    'id' => $remark->id,
                    'unit_id' => $remark->unit_id,
                    'remark' => $remark->event,
                    'type' => $remark->type,
                    'created_at' => $remark->created_at,
                    'unit' => [
                        'id' => $remark->unit?->id,
                        'unit_number' => $remark->unit?->unit,
                        'property' => [
                            'id' => $remark->unit?->property?->id,
                            'project_name' => $remark->unit?->property?->project_name,
                        ]
                    ],
                    'created_by' => [
                        'full_name' => $remark->admin_name,
                        'email' => null, // admin_name is stored as string in unit_remarks
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'remarks' => $remarks,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more' => $paginated->hasMorePages()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch remarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export remarks to CSV
     */
    public function exportRemarks(Request $request)
    {
        try {
            $query = \App\Models\UnitRemark::with([
                'unit.property',
                'unit.users'
            ])
            ->orderBy('created_at', 'desc');

            // Apply same filters as getAllRemarks
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->input('date_from'));
            }
            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->input('date_to'));
            }
            if ($request->has('time')) {
                $query->where('time', $request->input('time'));
            }
            if ($request->has('unit')) {
                $query->whereHas('unit', function($q) use ($request) {
                    $q->where('unit', 'like', '%' . $request->input('unit') . '%');
                });
            }
            if ($request->has('project') && $request->input('project') !== 'all') {
                $query->whereHas('unit.property', function($q) use ($request) {
                    $q->where('project_name', $request->input('project'));
                });
            }

            $remarks = $query->get();

            // Create CSV content
            $csv = "Date,Time,Unit Number,Project,Remark,Type,Created By,Created At\n";
            
            foreach ($remarks as $remark) {
                $csv .= sprintf(
                    "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                    $remark->date,
                    $remark->time,
                    $remark->unit?->unit ?? 'N/A',
                    $remark->unit?->property?->project_name ?? 'N/A',
                    str_replace('"', '""', $remark->event), // Escape quotes in CSV
                    $remark->type ?? 'N/A',
                    $remark->admin_name ?? 'System',
                    $remark->created_at->format('Y-m-d H:i:s')
                );
            }

            return response($csv, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="remarks-export-' . date('Y-m-d-His') . '.csv"');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export remarks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

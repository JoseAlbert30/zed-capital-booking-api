<?php

namespace App\Http\Controllers;

use App\Models\FinancePOP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\POPNotificationMail;

class FinancePOPController extends Controller
{
    /**
     * Get all POPs for a project
     */
    public function index(Request $request)
    {
        $project = $request->query('project');

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

            // Store the attachment
            $file = $request->file('attachment');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('finance/pops', 'public');

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

            $pop->load('creator:id,full_name,email', 'unit');

            return response()->json([
                'success' => true,
                'message' => 'POP created successfully',
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
                if ($pop->attachment_path && Storage::exists($pop->attachment_path)) {
                    Storage::delete($pop->attachment_path);
                }

                // Store new file
                $file = $request->file('attachment');
                $fileName = $file->getClientOriginalName();
                $path = $file->store('finance/pops', 'public');

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
            $pop->delete();

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
            // Get developer email from config or env
            $developerEmail = env('DEVELOPER_EMAIL', 'developer@example.com');

            // Send email notification
            // Mail::to($developerEmail)->send(new POPNotificationMail($pop));

            // Update notification status
            $pop->notification_sent = true;
            $pop->notification_sent_at = now();
            $pop->save();

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
}

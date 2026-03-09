<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FinanceEmail;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FinanceEmailController extends Controller
{
    /**
     * Upload CSV file with finance email recipients for a project
     */
    public function uploadCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt|max:2048',
            'property_id' => 'required|integer|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $propertyId = $request->input('property_id');

        try {
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // First row should be headers
            $headers = array_map('trim', array_map('strtolower', $csvData[0]));
            
            // Validate headers
            $requiredHeaders = ['unit_id', 'recipient_name', 'email'];
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    return response()->json([
                        'message' => "CSV must contain '{$requiredHeader}' column",
                        'required_headers' => $requiredHeaders
                    ], 422);
                }
            }
            
            // Get column indices
            $unitIdIndex = array_search('unit_id', $headers);
            $nameIndex = array_search('recipient_name', $headers);
            $emailIndex = array_search('email', $headers);
            
            // Skip the header row
            $data = array_slice($csvData, 1);
            
            $imported = 0;
            $errors = [];
            $skipped = 0;
            
            DB::beginTransaction();
            
            foreach ($data as $rowIndex => $row) {
                $lineNumber = $rowIndex + 2; // +2 because we start from 1 and skipped header
                
                // Skip empty rows
                if (empty($row) || empty($row[$unitIdIndex])) {
                    $skipped++;
                    continue;
                }
                
                $unitId = trim($row[$unitIdIndex]);
                $recipientName = trim($row[$nameIndex] ?? '');
                $email = trim($row[$emailIndex] ?? '');
                
                // Validate email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Line {$lineNumber}: Invalid email format '{$email}'";
                    continue;
                }
                
                // Verify unit exists and belongs to the property
                $unit = Unit::where('id', $unitId)
                    ->where('property_id', $propertyId)
                    ->first();
                    
                if (!$unit) {
                    $errors[] = "Line {$lineNumber}: Unit ID '{$unitId}' not found in this project";
                    continue;
                }
                
                // Check if email already exists for this unit
                $existing = FinanceEmail::where('unit_id', $unitId)
                    ->where('email', $email)
                    ->first();
                    
                if ($existing) {
                    // Update existing record
                    $existing->update([
                        'recipient_name' => $recipientName,
                        'type' => 'buyer',
                    ]);
                } else {
                    // Create new record
                    FinanceEmail::create([
                        'unit_id' => $unitId,
                        'email' => $email,
                        'recipient_name' => $recipientName,
                        'type' => 'buyer',
                        'is_primary' => false,
                    ]);
                }
                
                $imported++;
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'CSV processed successfully',
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process CSV',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all finance emails for a project
     */
    public function getByProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $propertyId = $request->input('property_id');
        
        $financeEmails = FinanceEmail::whereHas('unit', function($query) use ($propertyId) {
            $query->where('property_id', $propertyId);
        })
        ->with('unit:id,unit_number,property_id')
        ->orderBy('unit_id')
        ->get();
        
        return response()->json([
            'finance_emails' => $financeEmails
        ]);
    }
    
    /**
     * Delete all finance emails for a project
     */
    public function deleteByProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer|exists:properties,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $propertyId = $request->input('property_id');
        
        $deleted = FinanceEmail::whereHas('unit', function($query) use ($propertyId) {
            $query->where('property_id', $propertyId);
        })->delete();
        
        return response()->json([
            'message' => 'Finance emails deleted successfully',
            'deleted' => $deleted
        ]);
    }
    
    /**
     * Delete a single finance email
     */
    public function delete($id)
    {
        $financeEmail = FinanceEmail::find($id);
        
        if (!$financeEmail) {
            return response()->json([
                'message' => 'Finance email not found'
            ], 404);
        }
        
        $financeEmail->delete();
        
        return response()->json([
            'message' => 'Finance email deleted successfully'
        ]);
    }
    
    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $csv = "unit_id,recipient_name,email\n";
        $csv .= "1,John Doe,john.doe@example.com\n";
        $csv .= "2,Jane Smith,jane.smith@example.com\n";
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="finance_email_template.csv"');
    }
}

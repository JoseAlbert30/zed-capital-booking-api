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
            
            // Validate required headers
            $requiredHeaders = ['unit_number', 'buyer 1 name', 'buyer 1 email'];
            foreach ($requiredHeaders as $requiredHeader) {
                if (!in_array($requiredHeader, $headers)) {
                    return response()->json([
                        'message' => "CSV must contain '{$requiredHeader}' column",
                        'required_headers' => ['unit_number', 'buyer 1 name', 'buyer 1 email', '(optional) buyer 2 name', '(optional) buyer 2 email', '...']
                    ], 422);
                }
            }
            
            // Dynamically detect all buyer N name / buyer N email column pairs
            $unitNumberIndex = array_search('unit_number', $headers);
            $buyerPairs = [];
            $buyerNum = 1;
            while (true) {
                $nameCol  = "buyer {$buyerNum} name";
                $emailCol = "buyer {$buyerNum} email";
                if (in_array($nameCol, $headers) && in_array($emailCol, $headers)) {
                    $buyerPairs[] = [
                        'name_index'  => array_search($nameCol, $headers),
                        'email_index' => array_search($emailCol, $headers),
                        'is_primary'  => $buyerNum === 1,
                        'type'        => $buyerNum === 1 ? 'buyer' : 'co-buyer',
                    ];
                    $buyerNum++;
                } else {
                    break;
                }
            }
            
            // Skip the header row
            $data = array_slice($csvData, 1);
            
            $importedUnits = 0;
            $importedBuyers = 0;
            $errors = [];
            $skipped = 0;
            
            DB::beginTransaction();
            
            foreach ($data as $rowIndex => $row) {
                $lineNumber = $rowIndex + 2;
                
                // Skip empty rows
                if (empty($row) || empty($row[$unitNumberIndex])) {
                    $skipped++;
                    continue;
                }
                
                $unitNumber = trim($row[$unitNumberIndex]);
                
                // Verify unit exists by unit_number and belongs to the property
                $unit = Unit::where('unit_number', $unitNumber)
                    ->where('property_id', $propertyId)
                    ->first();
                    
                if (!$unit) {
                    $errors[] = "Line {$lineNumber}: Unit '{$unitNumber}' not found in this project";
                    continue;
                }
                
                // Validate that at least buyer 1 email is present and valid
                $buyer1EmailRaw = trim($row[$buyerPairs[0]['email_index']] ?? '');
                if (empty($buyer1EmailRaw)) {
                    $errors[] = "Line {$lineNumber}: Buyer 1 email is required";
                    continue;
                }
                if (!filter_var($buyer1EmailRaw, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Line {$lineNumber}: Invalid email format for buyer 1 '{$buyer1EmailRaw}'";
                    continue;
                }
                
                // Delete all existing finance emails for this unit before re-inserting
                FinanceEmail::where('unit_id', $unit->id)->delete();
                
                // Insert all buyer entries for this unit
                foreach ($buyerPairs as $pair) {
                    $email = trim($row[$pair['email_index']] ?? '');
                    $name  = trim($row[$pair['name_index']] ?? '');
                    
                    // Skip empty optional buyers
                    if (empty($email)) {
                        continue;
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Line {$lineNumber}: Invalid email format '{$email}' — skipped";
                        continue;
                    }
                    
                    FinanceEmail::create([
                        'unit_id'        => $unit->id,
                        'email'          => $email,
                        'recipient_name' => $name,
                        'type'           => $pair['type'],
                        'is_primary'     => $pair['is_primary'],
                    ]);
                    
                    $importedBuyers++;
                }
                
                $importedUnits++;
            }
            
            DB::commit();
            
            return response()->json([
                'message'         => 'CSV processed successfully',
                'imported'        => $importedUnits,
                'imported_buyers' => $importedBuyers,
                'skipped'         => $skipped,
                'errors'          => $errors,
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
        $csv = "unit_number,buyer 1 name,buyer 1 email,buyer 2 name,buyer 2 email\n";
        $csv .= "A101,John Doe,john.doe@example.com,Jane Doe,jane.doe@example.com\n";
        $csv .= "B202,Bob Smith,bob.smith@example.com,,\n";
        
        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="finance_email_template.csv"');
    }
}

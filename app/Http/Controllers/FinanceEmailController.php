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

            // First row should be headers — normalise: trim, lowercase, collapse spaces
            // Also strip UTF-8 BOM that Excel adds to CSV files
            $headers = array_map(function ($h) {
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // strip BOM
                return preg_replace('/\s+/', ' ', strtolower(trim($h)));
            }, $csvData[0]);

            // Accept common variations for the unit column
            $unitColAliases = ['unit_number', 'unit no.', 'unit no', 'unit number', 'unit'];
            $unitNumberIndex = null;
            foreach ($unitColAliases as $alias) {
                $idx = array_search($alias, $headers);
                if ($idx !== false) {
                    $unitNumberIndex = $idx;
                    break;
                }
            }

            if ($unitNumberIndex === null) {
                return response()->json([
                    'message' => 'CSV must contain a unit column (e.g. "unit_number" or "UNIT NO.")',
                ], 422);
            }

            // Dynamically detect all "buyer N name" / "buyer N email" column pairs
            $buyerPairs = [];
            $buyerNum   = 1;
            while (true) {
                $nameCol  = "buyer {$buyerNum} name";
                $emailCol = "buyer {$buyerNum} email";
                $ni = array_search($nameCol, $headers);
                $ei = array_search($emailCol, $headers);
                if ($ni !== false && $ei !== false) {
                    $buyerPairs[] = ['name_index' => $ni, 'email_index' => $ei];
                    $buyerNum++;
                } else {
                    break;
                }
            }

            if (empty($buyerPairs)) {
                return response()->json([
                    'message' => 'CSV must contain at least "buyer 1 name" and "buyer 1 email" columns',
                ], 422);
            }

            // Skip the header row
            $data = array_slice($csvData, 1);

            $importedUnits  = 0;
            $importedBuyers = 0;
            $errors  = [];
            $skipped = 0;

            // Placeholder values that mean "no buyer"
            $placeholders = ['-', '–', '—', 'n/a', 'none', ''];

            DB::beginTransaction();

            foreach ($data as $rowIndex => $row) {
                $lineNumber = $rowIndex + 2;

                // Skip completely empty rows
                if (empty($row) || !isset($row[$unitNumberIndex])) {
                    $skipped++;
                    continue;
                }

                $unitNumber = trim($row[$unitNumberIndex]);
                if ($unitNumber === '') {
                    $skipped++;
                    continue;
                }

                // Find unit by unit name within this property
                $unit = Unit::where('unit', $unitNumber)
                    ->where('property_id', $propertyId)
                    ->first();

                if (!$unit) {
                    $errors[] = "Line {$lineNumber}: Unit '{$unitNumber}' not found in this project";
                    continue;
                }

                // Build the flat list of {name, email} entries for this unit,
                // expanding any "/" in an email field into extra CC entries.
                $entries = [];
                foreach ($buyerPairs as $pair) {
                    $rawEmail = trim($row[$pair['email_index']] ?? '');
                    $rawName  = trim($row[$pair['name_index']]  ?? '');

                    // Skip placeholder names / empty emails
                    if (in_array(strtolower($rawName), $placeholders) && $rawEmail === '') {
                        continue;
                    }
                    if ($rawEmail === '') {
                        continue;
                    }

                    // Split on "/" to handle "email1 / email2" patterns
                    $emailParts = array_filter(array_map('trim', preg_split('/\s*\/\s*/', $rawEmail)));

                    foreach ($emailParts as $emailPart) {
                        if ($emailPart === '') continue;
                        $entries[] = ['name' => $rawName, 'email' => $emailPart];
                    }
                }

                // Skip units with no valid buyer info (e.g. all "-" rows)
                if (empty($entries)) {
                    $skipped++;
                    continue;
                }

                // Validate the first (primary) email
                if (!filter_var($entries[0]['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Line {$lineNumber}: Invalid primary email '{$entries[0]['email']}' — row skipped";
                    continue;
                }

                // Delete existing and re-insert
                FinanceEmail::where('unit_id', $unit->id)->delete();

                foreach ($entries as $i => $entry) {
                    if (!filter_var($entry['email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Line {$lineNumber}: Invalid email '{$entry['email']}' — skipped";
                        continue;
                    }

                    FinanceEmail::create([
                        'unit_id'        => $unit->id,
                        'email'          => $entry['email'],
                        'recipient_name' => $entry['name'],
                        'type'           => $i === 0 ? 'buyer' : 'co-buyer',
                        'is_primary'     => $i === 0,
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
        ->with('unit:id,unit,property_id')
        ->orderBy('unit_id')
        ->get()
        ->map(function ($fe) {
            $fe->unit_number = $fe->unit?->unit ?? null;
            return $fe;
        });
        
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

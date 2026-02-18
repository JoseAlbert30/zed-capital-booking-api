<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesOffer;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\GenerateAllSalesOffers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalesOfferController extends Controller
{
    public function index()
    {
        $offers = SalesOffer::orderBy('created_at', 'desc')->get();
        return response()->json($offers);
    }

    public function uploadCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        
        // Skip the header rows (first 2 rows based on the CSV structure)
        $data = array_slice($csvData, 2);
        
        // Clear existing offers
        SalesOffer::truncate();
        
        foreach ($data as $row) {
            if (empty($row[0]) || $row[0] === '') {
                continue; // Skip empty rows
            }
            
            // Extract data from CSV
            $unitNo = $row[0];
            $bedrooms = $row[1];
            $sqft = floatval($row[2]);
            $price5050 = floatval($row[3]);
            $dld5050 = floatval($row[4]);
            $price3070 = !empty($row[5]) ? floatval($row[5]) : null;
            $dld3070 = !empty($row[6]) ? floatval($row[6]) : null;
            $adminFee = floatval($row[7]);
            
            // Create offer
            SalesOffer::create([
                'project_name' => 'Gate Eleven',
                'unit_no' => $unitNo,
                'bedrooms' => $this->extractBedroomNumber($bedrooms),
                'sqft' => $sqft,
                'price_5050' => $price5050,
                'dld_5050' => $dld5050,
                'price_3070' => $price3070,
                'dld_3070' => $dld3070,
                'admin_fee' => $adminFee,
            ]);
        }
        
        $count = SalesOffer::count();
        return response()->json([
            'message' => 'CSV uploaded successfully',
            'offers_created' => $count
        ]);
    }
    
    private function extractBedroomNumber($bedroomString)
    {
        // Extract number from strings like "2 BHK PLUS SR", "1 BHK", etc.
        preg_match('/(\d+)/', $bedroomString, $matches);
        return isset($matches[1]) ? intval($matches[1]) : 1;
    }

    public function generateOffer(Request $request, $offerId)
    {
        $request->validate([
            'payment_plan' => 'required|in:5050,3070',
        ]);

        $offer = SalesOffer::findOrFail($offerId);
        $paymentPlan = $request->payment_plan;

        // Determine price and DLD based on payment plan
        if ($paymentPlan === '5050') {
            $price = $offer->price_5050;
            $dldFee = $offer->dld_5050;
            $paymentPlanName = '50/50 Payment Plan';
        } else {
            $price = $offer->price_3070;
            $dldFee = $offer->dld_3070;
            $paymentPlanName = '30/70 Payment Plan';
        }

        // Calculate total price
        $totalPrice = $price + $dldFee + $offer->admin_fee;

        // Determine unit type based on bedrooms
        $unitType = $offer->bedrooms === 1 ? 'A2' : 'B3';

        // Load logo as base64
        $logo = null;
        if (\Storage::disk('public')->exists('letterheads/amwaj.png')) {
            $logo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/amwaj.png'));
        }

        // Load Zed logo for footer
        $zedLogo = null;
        if (\Storage::disk('public')->exists('letterheads/zed.png')) {
            $zedLogo = 'data:image/png;base64,' . base64_encode(\Storage::disk('public')->get('letterheads/zed.png'));
        }

        $data = [
            'offer' => $offer,
            'price' => $price,
            'dldFee' => $dldFee,
            'totalPrice' => $totalPrice,
            'paymentPlan' => $paymentPlanName,
            'unitType' => $unitType,
            'logo' => $logo,
            'zedLogo' => $zedLogo,
        ];

        $pdf = Pdf::loadView('sales-offer', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'Sales_Offer_' . $offer->unit_no . '_' . $paymentPlan . '.pdf';

        return $pdf->download($filename);
    }

    public function bulkGenerate()
    {
        $batchId = Str::uuid();
        
        GenerateAllSalesOffers::dispatch($batchId);

        return response()->json([
            'message' => 'Bulk generation started',
            'batch_id' => $batchId
        ]);
    }

    public function downloadBulk($batchId)
    {
        $zipPath = storage_path('app/public/sales_offers_' . $batchId . '.zip');

        if (!file_exists($zipPath)) {
            return response()->json([
                'status' => 'processing',
                'message' => 'Offers are still being generated'
            ], 202);
        }

        return response()->download($zipPath, 'All_Sales_Offers.zip')->deleteFileAfterSend(true);
    }

    public function checkBulkStatus($batchId)
    {
        $zipPath = storage_path('app/public/sales_offers_' . $batchId . '.zip');

        if (file_exists($zipPath)) {
            return response()->json([
                'status' => 'completed',
                'batch_id' => $batchId
            ]);
        }

        return response()->json([
            'status' => 'processing'
        ]);
    }
}

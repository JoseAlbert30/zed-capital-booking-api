<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SalesOffer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateAllSalesOffers implements ShouldQueue
{
    use Queueable;

    public $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $offers = SalesOffer::all();
        $zipPath = storage_path('app/public/sales_offers_' . $this->batchId . '.zip');
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($offers as $offer) {
                // Generate 50/50 plan
                $this->generateOfferPDF($offer, '5050', $zip);

                // Generate 30/70 plan if available
                if ($offer->price_3070 && $offer->dld_3070) {
                    $this->generateOfferPDF($offer, '3070', $zip);
                }
            }

            $zip->close();
        }
    }

    private function generateOfferPDF($offer, $paymentPlan, $zip)
    {
        if ($paymentPlan === '5050') {
            $price = $offer->price_5050;
            $dldFee = $offer->dld_5050;
            $paymentPlanName = '50/50 Payment Plan';
        } else {
            $price = $offer->price_3070;
            $dldFee = $offer->dld_3070;
            $paymentPlanName = '30/70 Payment Plan';
        }

        $totalPrice = $price + $dldFee + $offer->admin_fee;
        $unitType = $offer->bedrooms === 1 ? 'A2' : 'B3';

        // Load logo as base64
        $logo = null;
        if (Storage::disk('public')->exists('attachment/letterheads/amwaj.png')) {
            $logo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('attachment/letterheads/amwaj.png'));
        }

        // Load Zed logo for footer
        $zedLogo = null;
        if (Storage::disk('public')->exists('letterheads/zed.png')) {
            $zedLogo = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get('letterheads/zed.png'));
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
        $pdfContent = $pdf->output();

        $zip->addFromString($filename, $pdfContent);
    }
}

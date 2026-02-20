<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\SalesOfferController;
use App\Http\Controllers\FinancePOPController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/magic-link', [AuthController::class, 'validateMagicLink']);
});

// Public route for viewing attachments (no auth required for file viewing)
Route::get('/users/{user}/attachments/{attachment}', [UserController::class, 'viewAttachment']);

// Public route for utilities guide (no auth required for email links)
Route::get('/public/units/{unit}/utilities-guide', [UnitController::class, 'generateUtilitiesGuide'])->name('public.utilities-guide');

// Serve storage files with CORS (no auth required for PDF templates)
Route::get('/storage/{path}', function ($path) {
    // Decode the URL-encoded path to handle spaces and special characters
    $decodedPath = urldecode($path);
    $filePath = storage_path('app/public/' . $decodedPath);
    
    if (!file_exists($filePath)) {
        abort(404);
    }
    
    return response()->file($filePath);
})->where('path', '.*');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [UserController::class, 'profile']);

    Route::prefix('users')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::get('/all', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/by-email', [UserController::class, 'getUserByEmail']);
        Route::post('/create-with-unit', [UserController::class, 'createWithUnit']);
        Route::get('/test-bulk-upload', [UserController::class, 'testBulkUpload']);
        Route::post('/bulk', [UserController::class, 'bulkUpload']);
        Route::put('/{user}/payment-status', [UserController::class, 'updatePaymentStatus']);
        Route::post('/{user}/regenerate-password', [UserController::class, 'regeneratePassword']);
        Route::post('/{user}/remarks', [UserController::class, 'addRemark']);
        Route::post('/{user}/upload-attachment', [UserController::class, 'uploadAttachment']);
        Route::delete('/{user}/attachments/{attachment}', [UserController::class, 'deleteAttachment']);
        Route::get('/{user}/handover-status', [UserController::class, 'getHandoverStatus']);
        Route::put('/{user}/mortgage-status', [UserController::class, 'updateMortgageStatus']);
        Route::post('/{user}/send-booking-link', [UserController::class, 'sendBookingLink']);
    });

    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/my-bookings', [BookingController::class, 'userBookings']);
        Route::get('/eligible-units', [BookingController::class, 'eligibleUnits']);
        Route::post('/', [BookingController::class, 'store']);
        Route::get('/available-slots', [BookingController::class, 'availableSlots']);
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::put('/{booking}', [BookingController::class, 'update']);
        Route::delete('/{booking}', [BookingController::class, 'destroy']);
        Route::post('/{booking}/approve-poa', [BookingController::class, 'approvePoaBooking']);
        Route::get('/{booking}/templates', [BookingController::class, 'getTemplates']);
        Route::post('/{booking}/upload-handover-file', [BookingController::class, 'uploadHandoverFile']);
        Route::delete('/{booking}/delete-handover-file', [BookingController::class, 'deleteHandoverFile']);
        Route::post('/{booking}/complete-handover', [BookingController::class, 'completeHandover']);
        Route::post('/{booking}/generate-declaration', [BookingController::class, 'generateDeclaration']);
        Route::post('/{booking}/generate-handover-checklist', [BookingController::class, 'generateHandoverChecklist']);
        Route::post('/{booking}/save-declaration-signatures', [BookingController::class, 'saveDeclarationSignatures']);
        Route::get('/{booking}/download-blank-declaration-template', [BookingController::class, 'downloadBlankDeclarationTemplate']);
        Route::get('/{booking}/download-blank-checklist-template', [BookingController::class, 'downloadBlankChecklistTemplate']);
        
        // Snagging defects
        Route::get('/{booking}/snagging-defects', [BookingController::class, 'getSnaggingDefects']);
        Route::post('/{booking}/snagging-defects', [BookingController::class, 'createSnaggingDefect']);
        Route::put('/{booking}/snagging-defects/{defect}', [BookingController::class, 'updateSnaggingDefect']);
        Route::delete('/{booking}/snagging-defects/{defect}', [BookingController::class, 'deleteSnaggingDefect']);
    });

    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::get('/by-project', [UnitController::class, 'getUnitsByProject']);
        Route::post('/', [UnitController::class, 'store']);
        Route::post('/bulk', [UnitController::class, 'bulkUpload']);
        Route::post('/bulk-upload-soa', [UnitController::class, 'bulkUploadSOA']);
        Route::post('/upload-payment-details', [UnitController::class, 'uploadPaymentDetails']);
        Route::post('/bulk-send-handover', [UnitController::class, 'bulkSendHandoverEmail']);
        Route::post('/bulk-send-soa-email', [UnitController::class, 'bulkSendSOAEmail']);
        Route::post('/check-soa-status', [UnitController::class, 'checkSOAStatus']);
        Route::post('/bulk-generate-soa', [UnitController::class, 'bulkGenerateSOA']);
        Route::post('/calibrate-amounts', [UnitController::class, 'calibrateAllAmounts']);
        Route::get('/download-all-soas', [UnitController::class, 'downloadAllSOAs']);
        Route::post('/download-selected-soas', [UnitController::class, 'downloadSelectedSOAs']);
        Route::get('/download-handover-status-report', [UnitController::class, 'downloadHandoverStatusReport']);
        Route::get('/download-all-utilities-guides', [UnitController::class, 'downloadAllUtilitiesGuides']);
        Route::get('/utilities-guides-batch/{batchId}/progress', [UnitController::class, 'getUtilitiesGuidesBatchProgress']);
        Route::get('/utilities-guides-batch/{batchId}/download', [UnitController::class, 'downloadUtilitiesGuidesZip']);
        Route::get('/handover-batch/{batchId}/progress', [UnitController::class, 'getHandoverEmailProgress']);
        Route::get('/soa-batch/{batchId}/progress', [UnitController::class, 'getSOAGenerationProgress']);
        
        // Unit-centric workflow endpoints (specific routes before generic {unit})
        Route::post('/{unit}/payment-details/update-only', [UnitController::class, 'updatePaymentDetailsOnly']);
        Route::post('/{unit}/payment-details', [UnitController::class, 'updateSingleUnitPaymentDetails']);
        Route::put('/{unit}/payment-status', [UnitController::class, 'updatePaymentStatus']);
        Route::post('/{unit}/remarks', [UnitController::class, 'addRemark']);
        Route::post('/{unit}/send-soa', [UnitController::class, 'sendSOAEmail']);
        Route::post('/{unit}/send-handover-email', [UnitController::class, 'sendHandoverEmail']);
        Route::get('/{unit}/handover-status', [UnitController::class, 'getHandoverStatus']);
        Route::put('/{unit}/mortgage-status', [UnitController::class, 'updateMortgageStatus']);
        Route::post('/{unit}/validate-handover-requirements', [UnitController::class, 'validateHandoverRequirements']);
        Route::post('/{unit}/send-booking-link', [UnitController::class, 'sendBookingLink']);
        Route::post('/{unit}/upload-attachment', [UnitController::class, 'uploadAttachment']);
        Route::delete('/{unit}/attachments/{attachment}', [UnitController::class, 'deleteAttachment']);
        Route::get('/{unit}/service-charge-acknowledgement', [UnitController::class, 'generateServiceChargeAcknowledgement']);
        Route::get('/{unit}/utilities-guide', [UnitController::class, 'generateUtilitiesGuide']);
        Route::get('/{unit}/noc-handover', [UnitController::class, 'generateNOC']);
        Route::get('/{unit}/developer-requirements-preview', [UnitController::class, 'previewDeveloperRequirements']);
        Route::post('/{unit}/send-to-developer', [UnitController::class, 'sendRequirementsToDeveloper']);
        Route::post('/{unit}/generate-clearance', [UnitController::class, 'generateClearance']);
        
        Route::get('/{unit}', [UnitController::class, 'show']);
        Route::put('/{unit}', [UnitController::class, 'update']);
        Route::delete('/{unit}', [UnitController::class, 'destroy']);
    });

    Route::prefix('email')->group(function () {
        Route::post('/send-soa', [EmailController::class, 'sendSOAEmail']);
        Route::get('/logs', [EmailController::class, 'getAllEmailLogs']);
        Route::get('/logs/{userId}', [EmailController::class, 'getUserEmailLogs']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('/remarks', [UnitController::class, 'getAllRemarks']);
        Route::get('/remarks/export', [UnitController::class, 'exportRemarks']);
    });

    Route::prefix('sales-offers')->group(function () {
        Route::get('/', [SalesOfferController::class, 'index']);
        Route::post('/upload-csv', [SalesOfferController::class, 'uploadCsv']);
        Route::post('/{offer}/generate-offer', [SalesOfferController::class, 'generateOffer']);
        Route::post('/bulk-generate', [SalesOfferController::class, 'bulkGenerate']);
        Route::get('/bulk-download/{batchId}', [SalesOfferController::class, 'downloadBulk']);
        Route::get('/bulk-status/{batchId}', [SalesOfferController::class, 'checkBulkStatus']);
    });

    Route::get('/properties', function () {
        return response()->json(\App\Models\Property::all());
    });

    // Finance POP Routes
    Route::prefix('finance/pops')->group(function () {
        Route::get('/', [FinancePOPController::class, 'index']);
        Route::post('/', [FinancePOPController::class, 'store']);
        Route::put('/{id}', [FinancePOPController::class, 'update']);
        Route::delete('/{id}', [FinancePOPController::class, 'destroy']);
        Route::post('/{id}/notify', [FinancePOPController::class, 'sendNotification']);
    });

    Route::get('/health', function (Request $request) {
        return response()->json(['status' => 'ok', 'user' => $request->user()]);
    });
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

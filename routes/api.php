<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/magic-link', [AuthController::class, 'validateMagicLink']);
});

// Public route for viewing attachments (no auth required for file viewing)
Route::get('/users/{user}/attachments/{attachment}', [UserController::class, 'viewAttachment']);

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
        Route::get('/{booking}/templates', [BookingController::class, 'getTemplates']);
        Route::post('/{booking}/upload-handover-file', [BookingController::class, 'uploadHandoverFile']);
        Route::delete('/{booking}/delete-handover-file', [BookingController::class, 'deleteHandoverFile']);
        Route::post('/{booking}/complete-handover', [BookingController::class, 'completeHandover']);
    });

    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::get('/{unit}', [UnitController::class, 'show']);
        Route::post('/', [UnitController::class, 'store']);
        Route::post('/bulk', [UnitController::class, 'bulkUpload']);
        Route::put('/{unit}', [UnitController::class, 'update']);
        Route::delete('/{unit}', [UnitController::class, 'destroy']);
        
        // Unit-centric workflow endpoints
        Route::put('/{unit}/payment-status', [UnitController::class, 'updatePaymentStatus']);
        Route::post('/{unit}/remarks', [UnitController::class, 'addRemark']);
        Route::post('/{unit}/send-soa', [UnitController::class, 'sendSOAEmail']);
        Route::post('/{unit}/send-handover-email', [UnitController::class, 'sendHandoverEmail']);
        Route::get('/{unit}/handover-status', [UnitController::class, 'getHandoverStatus']);
        Route::put('/{unit}/mortgage-status', [UnitController::class, 'updateMortgageStatus']);
        Route::post('/{unit}/send-booking-link', [UnitController::class, 'sendBookingLink']);
        Route::post('/{unit}/upload-attachment', [UnitController::class, 'uploadAttachment']);
        Route::delete('/{unit}/attachments/{attachment}', [UnitController::class, 'deleteAttachment']);
    });

    Route::prefix('email')->group(function () {
        Route::post('/send-soa', [EmailController::class, 'sendSOAEmail']);
        Route::get('/logs', [EmailController::class, 'getAllEmailLogs']);
        Route::get('/logs/{userId}', [EmailController::class, 'getUserEmailLogs']);
    });

    Route::prefix('properties')->group(function () {
        Route::post('/{property}/upload-templates', [PropertyController::class, 'uploadTemplates']);
    });

    Route::get('/health', function (Request $request) {
        return response()->json(['status' => 'ok', 'user' => $request->user()]);
    });
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

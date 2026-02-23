<?php

namespace App\Http\Controllers;

use App\Models\DeveloperMagicLink;
use App\Models\FinancePOP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DeveloperPortalController extends Controller
{
    /**
     * Verify magic link and return developer session info
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $magicLink = DeveloperMagicLink::where('token', $token)->first();

        if (!$magicLink) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 404);
        }

        if (!$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => $magicLink->expires_at->isPast() 
                    ? 'This link has expired. Please contact support for a new link.'
                    : 'This link is no longer active',
            ], 403);
        }

        // Mark as used
        $magicLink->markAsUsed(
            $request->ip(),
            $request->header('User-Agent')
        );

        // Return different responses based on whether password is set
        $response = [
            'success' => true,
            'developer' => [
                'name' => $magicLink->developer_name,
                'email' => $magicLink->developer_email,
                'project' => $magicLink->project_name,
            ],
            'passwordSet' => $magicLink->password_set,
            'requiresPasswordSetup' => !$magicLink->password_set,
        ];

        // If password is already set, return the magic link token for authentication
        // This allows the developer to access the system immediately
        if ($magicLink->password_set) {
            $response['authToken'] = $token;
        } else {
            $response['token'] = $token;
        }

        return response()->json($response);
    }

    /**
     * Set password on first access
     */
    public function setPassword(Request $request)
    {
        $token = $request->input('token') ?? $request->header('X-Developer-Token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $magicLink = DeveloperMagicLink::where('token', $token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $magicLink->setPassword($request->password);

        return response()->json([
            'success' => true,
            'message' => 'Password set successfully',
            'authToken' => $token,
            'developer' => [
                'name' => $magicLink->developer_name,
                'email' => $magicLink->developer_email,
                'project' => $magicLink->project_name,
            ],
        ]);
    }

    /**
     * Login with email and password
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $magicLink = DeveloperMagicLink::where('developer_email', $request->email)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$magicLink) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials or no active access',
            ], 401);
        }

        if (!$magicLink->password_set) {
            return response()->json([
                'success' => false,
                'message' => 'Please use the magic link to set your password first',
            ], 401);
        }

        if (!$magicLink->checkPassword($request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Mark as used
        $magicLink->markAsUsed($request->ip(), $request->userAgent());

        return response()->json([
            'success' => true,
            'developer' => [
                'name' => $magicLink->developer_name,
                'email' => $magicLink->developer_email,
                'project' => $magicLink->project_name,
            ],
            'token' => $magicLink->token,
        ]);
    }

    /**
     * Get all POPs for the developer's project
     */
    public function getPOPs(Request $request)
    {
        $token = $request->header('X-Developer-Token') ?? $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $magicLink = DeveloperMagicLink::where('token', $token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 403);
        }

        // Get all POPs for this project
        $pops = FinancePOP::where('project_name', $magicLink->project_name)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'pops' => $pops->map(function ($pop) {
                return [
                    'id' => $pop->id,
                    'popNumber' => $pop->pop_number,
                    'unitNumber' => $pop->unit_number,
                    'amount' => (float) $pop->amount,
                    'attachmentUrl' => $pop->attachment_url,
                    'attachmentName' => $pop->attachment_name,
                    'receiptUrl' => $pop->receipt_url,
                    'receiptName' => $pop->receipt_name,
                    'receiptUploaded' => $pop->receipt_uploaded_at !== null,
                    'date' => $pop->created_at->format('Y-m-d'),
                ];
            }),
        ]);
    }

    /**
     * Upload receipt for a specific POP
     */
    public function uploadReceipt(Request $request, $popId)
    {
        $token = $request->header('X-Developer-Token') ?? $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $magicLink = DeveloperMagicLink::where('token', $token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 403);
        }

        $pop = FinancePOP::find($popId);

        if (!$pop) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found',
            ], 404);
        }

        // Verify POP belongs to developer's project
        if ($pop->project_name !== $magicLink->project_name) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to upload receipts for this POP',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Delete old receipt if exists
            if ($pop->receipt_path && Storage::disk('public')->exists($pop->receipt_path)) {
                Storage::disk('public')->delete($pop->receipt_path);
            }

            // Store new receipt
            $file = $request->file('receipt');
            $fileName = $file->getClientOriginalName();
            $path = $file->store('finance/receipts', 'public');

            // Update POP
            $pop->receipt_path = $path;
            $pop->receipt_name = $fileName;
            $pop->receipt_uploaded_at = now();
            $pop->save();

            return response()->json([
                'success' => true,
                'message' => 'Receipt uploaded successfully',
                'receipt' => [
                    'url' => $pop->receipt_url,
                    'name' => $pop->receipt_name,
                    'uploadedAt' => $pop->receipt_uploaded_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload receipt: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download POP attachment
     */
    public function downloadPOP(Request $request, $popId)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
            ], 400);
        }

        $magicLink = DeveloperMagicLink::where('token', $token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 403);
        }

        $pop = FinancePOP::find($popId);

        if (!$pop || $pop->project_name !== $magicLink->project_name) {
            return response()->json([
                'success' => false,
                'message' => 'POP not found or access denied',
            ], 404);
        }

        if (!$pop->attachment_path || !Storage::disk('public')->exists($pop->attachment_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment not found',
            ], 404);
        }

        return Storage::disk('public')->download($pop->attachment_path, $pop->attachment_name);
    }
}

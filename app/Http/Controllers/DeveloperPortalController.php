<?php

namespace App\Http\Controllers;

use App\Models\DeveloperMagicLink;
use App\Models\FinancePOP;
use App\Models\FinanceSOA;
use App\Models\FinanceNOC;
use App\Models\FinanceThirdparty;
use App\Events\PendingCountsUpdated;
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

        // Check if ANY magic link for this email has a password set
        // This allows the same developer to access multiple projects with one password
        $hasPasswordSet = DeveloperMagicLink::where('developer_email', $magicLink->developer_email)
            ->where('password_set', true)
            ->exists();

        // If password is set on another link but not this one, sync it
        if ($hasPasswordSet && !$magicLink->password_set) {
            // Get the password from any link that has it set
            $linkWithPassword = DeveloperMagicLink::where('developer_email', $magicLink->developer_email)
                ->where('password_set', true)
                ->first();
            
            if ($linkWithPassword) {
                $magicLink->password = $linkWithPassword->password;
                $magicLink->password_set = true;
                $magicLink->password_set_at = $linkWithPassword->password_set_at;
                $magicLink->save();
            }
        }

        // Return different responses based on whether password is set
        $response = [
            'success' => true,
            'developer' => [
                'name' => $magicLink->developer_name,
                'email' => $magicLink->developer_email,
                'project' => $magicLink->project_name,
            ],
            'passwordSet' => $hasPasswordSet,
            'requiresPasswordSetup' => !$hasPasswordSet,
        ];

        // If password is already set, return the magic link token for authentication
        // This allows the developer to access the system immediately
        if ($hasPasswordSet) {
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
     * Login with email and password (returns all projects)
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

        // Get any active magic link for this email
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

        // Get ALL projects this developer has access to
        $allProjects = DeveloperMagicLink::where('developer_email', $request->email)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->get()
            ->map(function($link) {
                return [
                    'project_name' => $link->project_name,
                    'token' => $link->token,
                    'expires_at' => $link->expires_at->format('Y-m-d'),
                    'last_accessed' => $link->last_used_at?->format('Y-m-d H:i:s'),
                ];
            });

        // Mark the first one as used (for the login action)
        $magicLink->markAsUsed($request->ip(), $request->userAgent());

        return response()->json([
            'success' => true,
            'developer' => [
                'name' => $magicLink->developer_name,
                'email' => $magicLink->developer_email,
            ],
            'projects' => $allProjects,
            'requiresProjectSelection' => $allProjects->count() > 1,
        ]);
    }

    /**
     * Select a specific project after login
     */
    public function selectProject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'project_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $magicLink = DeveloperMagicLink::where('developer_email', $request->email)
            ->where('project_name', $request->project_name)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$magicLink) {
            return response()->json([
                'success' => false,
                'message' => 'Project access not found',
            ], 404);
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
                    'buyerEmail' => $pop->buyer_email,
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

    /**
     * Get all projects for a developer (by email)
     */
    public function getMyProjects(Request $request)
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
            ], 401);
        }

        // Get all projects for this developer's email
        $projects = DeveloperMagicLink::where('developer_email', $magicLink->developer_email)
            ->where('is_active', true)
            ->select('project_name', 'developer_name', 'developer_email', 'last_used_at', 'expires_at')
            ->get()
            ->map(function($link) {
                return [
                    'project_name' => $link->project_name,
                    'developer_name' => $link->developer_name,
                    'developer_email' => $link->developer_email,
                    'last_accessed' => $link->last_used_at?->format('Y-m-d H:i:s'),
                    'expires_at' => $link->expires_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }

    /**
     * Get list of all people with access to a project (Admin only for now, or developer can see their own project)
     */
    public function getProjectAccessList(Request $request, $projectName)
    {
        // Decode project name from URL
        $projectName = urldecode($projectName);

        // Get all active magic links for this project
        $accessList = DeveloperMagicLink::where('project_name', $projectName)
            ->where('is_active', true)
            ->select('developer_name', 'developer_email', 'project_name', 'first_used_at', 'last_used_at', 'access_count', 'expires_at', 'password_set')
            ->orderBy('developer_name')
            ->get()
            ->map(function($link) {
                return [
                    'name' => $link->developer_name,
                    'email' => $link->developer_email,
                    'project' => $link->project_name,
                    'first_accessed' => $link->first_used_at?->format('Y-m-d H:i:s'),
                    'last_accessed' => $link->last_used_at?->format('Y-m-d H:i:s'),
                    'access_count' => $link->access_count,
                    'expires_at' => $link->expires_at->format('Y-m-d'),
                    'has_password' => $link->password_set,
                ];
            });

        return response()->json([
            'success' => true,
            'access_list' => $accessList,
            'project_name' => $projectName,
        ]);
    }

    /**
     * Get pending counts for developer sidebar
     * Returns counts of unfulfilled POPs, SOAs, NOCs, and Thirdparty requests
     */
    public function getPendingCounts(Request $request)
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
            ], 401);
        }

        // Get all projects for this developer's email
        $projects = DeveloperMagicLink::where('developer_email', $magicLink->developer_email)
            ->where('is_active', true)
            ->pluck('project_name')
            ->unique();

        $totalCounts = [
            'pop' => 0,
            'soa' => 0,
            'noc' => 0,
            'thirdparty' => 0,
            'total' => 0,
        ];

        $projectCounts = [];

        foreach ($projects as $projectName) {
            // Count POPs without receipts (in POP kanban - developer needs to upload receipt)
            $popCount = FinancePOP::where('project_name', $projectName)
                ->whereNull('receipt_path')
                ->count();

            // Count SOA requests without documents
            $soaCount = FinanceSOA::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();

            // Count NOCs without documents
            $nocCount = FinanceNOC::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();

            // Count Thirdparty requests without receipts
            $thirdpartyCount = FinanceThirdparty::where('project_name', $projectName)
                ->whereNull('receipt_document_path')
                ->count();

            $projectTotal = $popCount + $soaCount + $nocCount + $thirdpartyCount;

            $projectCounts[$projectName] = [
                'pop' => $popCount,
                'soa' => $soaCount,
                'noc' => $nocCount,
                'thirdparty' => $thirdpartyCount,
                'total' => $projectTotal,
            ];

            $totalCounts['pop'] += $popCount;
            $totalCounts['soa'] += $soaCount;
            $totalCounts['noc'] += $nocCount;
            $totalCounts['thirdparty'] += $thirdpartyCount;
            $totalCounts['total'] += $projectTotal;
        }

        return response()->json([
            'success' => true,
            'counts' => $totalCounts,
            'projects' => $projectCounts,
        ]);
    }

    /**
     * Broadcast pending counts update for a developer
     * Can be called from any controller after upload actions
     */
    public static function broadcastPendingCounts($developerEmail)
    {
        // Get all projects for this developer's email
        $projects = DeveloperMagicLink::where('developer_email', $developerEmail)
            ->where('is_active', true)
            ->pluck('project_name')
            ->unique();

        $totalCounts = [
            'pop' => 0,
            'soa' => 0,
            'noc' => 0,
            'thirdparty' => 0,
            'total' => 0,
        ];

        $projectCounts = [];

        foreach ($projects as $projectName) {
            // Count POPs without receipts
            $popCount = FinancePOP::where('project_name', $projectName)
                ->whereNull('receipt_path')
                ->count();

            // Count SOA requests without documents
            $soaCount = FinanceSOA::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();

            // Count NOCs without documents
            $nocCount = FinanceNOC::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();

            // Count Thirdparty requests without receipts
            $thirdpartyCount = FinanceThirdparty::where('project_name', $projectName)
                ->whereNull('receipt_document_path')
                ->count();

            $projectTotal = $popCount + $soaCount + $nocCount + $thirdpartyCount;

            $projectCounts[$projectName] = [
                'pop' => $popCount,
                'soa' => $soaCount,
                'noc' => $nocCount,
                'thirdparty' => $thirdpartyCount,
                'total' => $projectTotal,
            ];

            $totalCounts['pop'] += $popCount;
            $totalCounts['soa'] += $soaCount;
            $totalCounts['noc'] += $nocCount;
            $totalCounts['thirdparty'] += $thirdpartyCount;
            $totalCounts['total'] += $projectTotal;
        }

        // Broadcast the event
        event(new PendingCountsUpdated($developerEmail, [
            'total' => $totalCounts,
            'projects' => $projectCounts,
        ]));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DevUser;
use App\Models\DeveloperMagicLink;
use App\Models\FinanceAccess;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DevAuthController extends Controller
{
    /**
     * Validate magic link and get project info
     */
    public function validateMagicLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        $magicLink = DeveloperMagicLink::where('token', $request->token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired magic link',
            ], 404);
        }

        // Mark as used
        $magicLink->markAsUsed(
            $request->ip(),
            $request->header('User-Agent')
        );

        return response()->json([
            'success' => true,
            'project_name' => $magicLink->project_name,
            'email' => $magicLink->developer_email,
            'token' => $magicLink->token,
        ]);
    }

    /**
     * Register a new developer or grant access to existing developer
     */
    public function registerOrGrantAccess(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate magic link
        $magicLink = DeveloperMagicLink::where('token', $request->token)->first();

        if (!$magicLink || !$magicLink->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired magic link',
            ], 404);
        }

        // Verify email is allowed for this project
        $property = Property::where('project_name', $magicLink->project_name)->first();
        if (!$property) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], 404);
        }

        $allowedEmails = array_merge(
            [$property->developer_email],
            $property->cc_emails ? array_map('trim', explode(',', $property->cc_emails)) : []
        );

        if (!in_array($request->email, $allowedEmails)) {
            return response()->json([
                'success' => false,
                'message' => 'Email not authorized for this project',
            ], 403);
        }

        // Check if developer already exists
        $devUser = DevUser::where('email', $request->email)->first();

        if ($devUser) {
            // Existing developer - just grant access to this project
            FinanceAccess::grantAccess($devUser->id, $magicLink->project_name);

            // Generate token
            $token = $devUser->createToken('developer-access')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Access granted to project',
                'token' => $token,
                'user' => [
                    'id' => $devUser->id,
                    'name' => $devUser->name,
                    'email' => $devUser->email,
                ],
            ]);
        }

        // New developer - create account and grant access
        $devUser = DevUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Grant access to project
        FinanceAccess::grantAccess($devUser->id, $magicLink->project_name);

        // Generate token
        $token = $devUser->createToken('developer-access')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created and access granted',
            'token' => $token,
            'user' => [
                'id' => $devUser->id,
                'name' => $devUser->name,
                'email' => $devUser->email,
            ],
        ], 201);
    }

    /**
     * Login for existing developer
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

        $devUser = DevUser::where('email', $request->email)->first();

        if (!$devUser || !Hash::check($request->password, $devUser->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate token
        $token = $devUser->createToken('developer-access')->plainTextToken;

        // Get accessible projects
        $projects = FinanceAccess::getUserProjects($devUser->id);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $devUser->id,
                'name' => $devUser->name,
                'email' => $devUser->email,
            ],
            'projects' => $projects,
        ]);
    }

    /**
     * Logout developer
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get current developer info
     */
    public function me(Request $request)
    {
        $devUser = $request->user();
        $projects = FinanceAccess::getUserProjects($devUser->id);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $devUser->id,
                'name' => $devUser->name,
                'email' => $devUser->email,
            ],
            'projects' => $projects,
        ]);
    }

    /**
     * Get all projects accessible to the developer with details
     */
    public function myProjects(Request $request)
    {
        $devUser = $request->user();
        
        // Get all projects the developer has access to
        $projectNames = FinanceAccess::getUserProjects($devUser->id);
        
        // Get project details from properties table
        $projects = Property::whereIn('project_name', $projectNames)
            ->get()
            ->map(function ($property) use ($devUser) {
                $financeAccess = FinanceAccess::where('dev_user_id', $devUser->id)
                    ->where('project_name', $property->project_name)
                    ->first();
                
                return [
                    'project_name' => $property->project_name,
                    'developer_name' => $devUser->name,
                    'developer_email' => $devUser->email,
                    'last_accessed' => $financeAccess->updated_at ?? null,
                    'expires_at' => null, // Can implement expiration logic later
                ];
            });

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }

    /**
     * Get pending counts for all developer's projects
     */
    public function pendingCounts(Request $request)
    {
        $devUser = $request->user();
        
        // Get all projects the developer has access to
        $projectNames = FinanceAccess::getUserProjects($devUser->id);
        
        $projectCounts = [];
        $totalCounts = [
            'pop' => 0,
            'soa' => 0,
            'noc' => 0,
            'thirdparty' => 0,
            'total' => 0,
        ];
        
        foreach ($projectNames as $projectName) {
            // POPs pending receipt upload (developer action)
            $popCount = \App\Models\FinancePOP::where('project_name', $projectName)
                ->whereNull('receipt_path')
                ->count();
            
            // SOAs pending document upload (admin action - not counted for developer)
            $soaCount = \App\Models\FinanceSOA::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();
            
            // NOCs pending document upload (admin action - not counted for developer)
            $nocCount = \App\Models\FinanceNOC::where('project_name', $projectName)
                ->whereNull('document_path')
                ->count();
            
            // Penalties pending receipt upload (developer action)
            $penaltyCount = \App\Models\FinancePenalty::where('project_name', $projectName)
                ->whereNotNull('proof_of_payment_path') // Only count if admin has uploaded proof
                ->whereNull('receipt_path')
                ->count();
            
            // Thirdparty pending receipt upload (developer action)
            $thirdpartyCount = \App\Models\FinanceThirdparty::where('project_name', $projectName)
                ->where('sent_to_developer', true)
                ->whereNull('receipt_document_path')
                ->count();
            
            $projectTotal = $popCount + $penaltyCount + $thirdpartyCount;
            
            $projectCounts[$projectName] = [
                'pop' => $popCount,
                'soa' => $soaCount,
                'noc' => $nocCount,
                'penalty' => $penaltyCount,
                'thirdparty' => $thirdpartyCount,
                'total' => $projectTotal,
            ];
            
            $totalCounts['pop'] += $popCount;
            $totalCounts['soa'] += $soaCount;
            $totalCounts['noc'] += $nocCount;
            $totalCounts['total'] += $projectTotal;
        }
        
        // Add penalty and thirdparty to total counts for consistency
        $totalCounts['penalty'] = array_sum(array_column($projectCounts, 'penalty'));
        $totalCounts['thirdparty'] = array_sum(array_column($projectCounts, 'thirdparty'));
        
        return response()->json([
            'success' => true,
            'counts' => $totalCounts,
            'projects' => $projectCounts,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DevUser;
use App\Models\FinanceAccess;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminDevUserController extends Controller
{
    /**
     * Get all developers with their project access
     */
    public function index()
    {
        $developers = DevUser::with('financeAccess')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'developers' => $developers->map(function ($dev) {
                $projects = $dev->financeAccess()
                    ->where('is_active', true)
                    ->get()
                    ->map(function ($access) {
                        return [
                            'id' => $access->id,
                            'project_name' => $access->project_name,
                            'granted_at' => $access->created_at->format('Y-m-d H:i:s'),
                        ];
                    });

                return [
                    'id' => $dev->id,
                    'name' => $dev->name,
                    'email' => $dev->email,
                    'created_at' => $dev->created_at->format('Y-m-d H:i:s'),
                    'must_reset_password' => $dev->must_reset_password ?? false,
                    'last_login' => $dev->last_login?->format('Y-m-d H:i:s'),
                    'projects' => $projects,
                    'project_count' => $projects->count(),
                ];
            }),
        ]);
    }

    /**
     * Create a new developer account
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:dev_users,email',
            'projects' => 'nullable|array',
            'projects.*' => 'string|exists:properties,project_name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate a random temporary password
        $temporaryPassword = Str::random(12);

        $developer = DevUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'must_reset_password' => true,
        ]);

        // Grant access to selected projects
        if ($request->projects) {
            foreach ($request->projects as $projectName) {
                FinanceAccess::grantAccess($developer->id, $projectName);
            }
        }

        // TODO: Send email with temporary password
        // Mail::to($developer->email)->send(new DeveloperAccountCreated($developer, $temporaryPassword));

        return response()->json([
            'success' => true,
            'message' => 'Developer account created successfully',
            'developer' => [
                'id' => $developer->id,
                'name' => $developer->name,
                'email' => $developer->email,
                'temporary_password' => $temporaryPassword, // Return for now, will be removed when email is implemented
            ],
        ], 201);
    }

    /**
     * Update developer information
     */
    public function update(Request $request, $id)
    {
        $developer = DevUser::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:dev_users,email,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $developer->name = $request->name;
        }

        if ($request->has('email')) {
            $developer->email = $request->email;
        }

        $developer->save();

        return response()->json([
            'success' => true,
            'message' => 'Developer updated successfully',
            'developer' => $developer,
        ]);
    }

    /**
     * Delete developer account
     */
    public function destroy($id)
    {
        $developer = DevUser::findOrFail($id);
        
        // Delete all access records
        FinanceAccess::where('dev_user_id', $id)->delete();
        
        // Delete the developer
        $developer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Developer account deleted successfully',
        ]);
    }

    /**
     * Reset developer password (admin action)
     */
    public function resetPassword($id)
    {
        $developer = DevUser::findOrFail($id);

        // Generate a new temporary password
        $temporaryPassword = Str::random(12);

        $developer->password = Hash::make($temporaryPassword);
        $developer->must_reset_password = true;
        $developer->save();

        // TODO: Send email with new temporary password
        // Mail::to($developer->email)->send(new DeveloperPasswordReset($developer, $temporaryPassword));

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully',
            'temporary_password' => $temporaryPassword, // Return for now, will be removed when email is implemented
        ]);
    }

    /**
     * Grant project access to developer
     */
    public function grantAccess(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string|exists:properties,project_name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $developer = DevUser::findOrFail($id);
        
        FinanceAccess::grantAccess($developer->id, $request->project_name);

        return response()->json([
            'success' => true,
            'message' => 'Project access granted successfully',
        ]);
    }

    /**
     * Revoke project access from developer
     */
    public function revokeAccess($id, $accessId)
    {
        $access = FinanceAccess::where('id', $accessId)
            ->where('dev_user_id', $id)
            ->firstOrFail();

        $access->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project access revoked successfully',
        ]);
    }

    /**
     * Get all available projects
     */
    public function getAvailableProjects()
    {
        $projects = Property::orderBy('project_name')->get(['id', 'project_name']);

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }
}

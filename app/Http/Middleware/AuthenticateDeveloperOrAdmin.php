<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DevUser;
use App\Models\FinanceAccess;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDeveloperOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $projectParam = null): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'No authorization token provided.',
            ], 401);
        }
        
        $token = substr($authHeader, 7);
        
        // Authenticate using Sanctum
        $accessToken = PersonalAccessToken::findToken($token);
        
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 401);
        }
        
        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);
        
        // Check if this is an admin (User) or developer (DevUser)
        $isAdmin = $user instanceof User;
        $isDeveloper = $user instanceof DevUser;
        
        if (!$isAdmin && !$isDeveloper) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid user type.',
            ], 403);
        }
        
        // Set auth type
        $request->attributes->set('auth_type', $isAdmin ? 'admin' : 'developer');
        
        // If project parameter is specified, check project access for developers
        if ($projectParam && $isDeveloper) {
            $projectName = $request->route($projectParam);
            
            if (!$projectName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project name is required.',
                ], 400);
            }
            
            if (!FinanceAccess::hasAccess($user->id, $projectName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have access to this project.',
                ], 403);
            }
            
            $request->attributes->set('developer_project', $projectName);
        }
        
        return $next($request);
    }
}

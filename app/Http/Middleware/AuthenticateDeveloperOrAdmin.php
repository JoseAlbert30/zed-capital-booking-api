<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeveloperMagicLink;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDeveloperOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'No authorization token provided.',
            ], 401);
        }
        
        $token = substr($authHeader, 7);
        
        // First, try to authenticate as admin using Sanctum
        $accessToken = PersonalAccessToken::findToken($token);
        
        if ($accessToken) {
            // Valid Sanctum token - user is an admin
            $user = $accessToken->tokenable;
            $request->setUserResolver(fn () => $user);
            $request->attributes->set('auth_type', 'admin');
            return $next($request);
        }
        
        // If not a Sanctum token, check for developer magic link token
        $magicLink = DeveloperMagicLink::where('token', $token)->first();
        
        if ($magicLink && $magicLink->isValid()) {
            // Valid developer token found
            $request->attributes->set('auth_type', 'developer');
            $request->attributes->set('developer_magic_link', $magicLink);
            $request->attributes->set('developer_project', $magicLink->project_name);
            return $next($request);
        }

        // Neither admin nor developer authentication succeeded
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Invalid or expired token.',
        ], 401);
    }
}

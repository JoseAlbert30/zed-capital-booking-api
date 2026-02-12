<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MagicLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile_number' => $request->mobile_number,
            'payment_status' => 'pending',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
            ->with(['units.property'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['units.property']);
        return response()->json($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful']);
    }

    /**
     * Validate magic link and authenticate user
     */
    public function validateMagicLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find magic link
        $magicLink = MagicLink::where('token', $request->token)
            ->with(['user.units.property', 'user.bookings'])
            ->first();

        if (!$magicLink) {
            return response()->json(['message' => 'Invalid booking link'], 404);
        }

        // Check if valid (not exceeded access limit and not expired)
        if (!$magicLink->isValid()) {
            if ($magicLink->access_count >= 3) {
                return response()->json(['message' => 'This booking link has expired (maximum access limit reached)'], 400);
            } else {
                return response()->json(['message' => 'This booking link has expired'], 400);
            }
        }

        // Mark as used
        $magicLink->markAsUsed(
            $request->ip(),
            $request->header('User-Agent')
        );

        // Generate authentication token
        $token = $magicLink->user->createToken('magic_link_auth')->plainTextToken;

        return response()->json([
            'message' => 'Authentication successful',
            'user' => $magicLink->user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
}

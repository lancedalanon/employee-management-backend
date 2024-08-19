<?php

namespace App\Services\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthenticationService
{
    public function login(array $validatedData): JsonResponse
    {
        // Attempt to authenticate the user using the provided credentials
        if (Auth::attempt(['username' => $validatedData['username'], 'password' => $validatedData['password']])) {
            // Retrieve the authenticated user
            $user = Auth::user();

            // Generate a Sanctum token for the authenticated user
            $token = $user->createToken(config('app.token_name', 'auth_token'))->plainTextToken;

            // Prepare the success response data
            $success = [
                'token' => $token,
                'username' => $user->username,
            ];

            // Return a success response with HTTP status code 200
            return response()->json([
                'message' => 'User logged in successfully.',
                'data' => $success,
            ], 200);
        }

        // Return an error response for unauthorized access with HTTP status code 401
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke the current user's token
        $request->user()->currentAccessToken()->delete();

        // Return a success response with HTTP status code 200
        return response()->json([
            'message' => 'User logged out successfully.',
        ], 200);
    }
}
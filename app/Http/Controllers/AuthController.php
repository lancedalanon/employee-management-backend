<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Attempt to authenticate the user and generate a Sanctum token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        // Attempt to authenticate the user using the provided credentials
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            // Retrieve the authenticated user
            $user = Auth::user();

            // Generate a Sanctum token for the authenticated user
            $token = $user->createToken('auth_token')->plainTextToken;

            // Prepare the success response data
            $success = [
                'token' => $token,
                'username' => $user->username,
            ];

            // Return a success response with HTTP status code 200
            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User logged in successfully.',
            ], 200);
        } else {
            // Return an error response for unauthorized access with HTTP status code 401
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'errors' => ['error' => 'Invalid credentials']
            ], 401);
        }
    }

    /**
     * Revoke the current user's token, effectively logging them out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current user's token
        $request->user()->currentAccessToken()->delete();

        // Return a success response with HTTP status code 200
        return response()->json(['message' => 'User logged out successfully.'], 200);
    }
}

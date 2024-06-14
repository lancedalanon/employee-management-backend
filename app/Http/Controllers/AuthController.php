<?php

namespace App\Http\Controllers;

use App\Models\User;
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
     */
    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
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

            // Return a success response
            return response()->json([
                'success' => true,
                'data' => $success,
                'message' => 'User login successfully.',
            ]);
        } else {
            // Return an error response for unauthorized access
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised.',
                'errors' => ['error' => 'Unauthorised']
            ], 401);
        }
    }
}

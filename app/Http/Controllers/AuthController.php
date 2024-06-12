<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Attempt to authenticate the user and generate a JWT token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Get credentials from the request
        $credentials = $request->only('username', 'password');

        try {
            // Attempt to authenticate the user
            if (!Auth::attempt($credentials)) {
                // Return error response if authentication fails
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Get the authenticated user
            $user = Auth::user();

            // Generate JWT token for the user
            $token = JWTAuth::fromUser($user);

            // Return JWT token in response
            return response()->json(['token' => $token]);
        } catch (JWTException $e) {
            // Return error response if token creation fails
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
}

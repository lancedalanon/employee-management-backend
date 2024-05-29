<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!Auth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $token = JWTAuth::fromUser(Auth::user());

            return response()->json(['token' => $token]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Get the token from the request (replace 'Authorization' header name if needed)
            $token = $request->header('Authorization');

            // If token is present, attempt to invalidate it
            if ($token) {
                JWTAuth::invalidate($token);
            }

            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not log out'], 500);
        }
    }
}

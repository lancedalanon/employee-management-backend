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

        // Attempt to authenticate the user with the provided username and password
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            // Authentication passed, generate token
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;
            $cookie = cookie('jwt', $token, 60 * 24); // 1 day

            // Return cookie  as a JSON response
            return response()->json(['message' => 'Success'])->withCookie($cookie);
        }

        // Authentication failed
        throw ValidationException::withMessages([
            'username' => ['The provided credentials are incorrect.'],
        ]);
    }
}

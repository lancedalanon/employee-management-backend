<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use App\Notifications\ResetPasswordNotification;

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
                'message' => 'User logged in successfully.',
                'data' => $success,
            ], 200);
        } else {
            // Return an error response for unauthorized access with HTTP status code 401
            return response()->json([
                'message' => 'Invalid credentials',
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
        return response()->json([
            'message' => 'User logged out successfully.',
        ], 200);
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the request
        $request->validate(['email' => 'required|email']);

        // Attempt to send the password reset link
        $response = Password::sendResetLink(
            $request->only('email')
        );

        // Check if the password reset link was sent successfully
        if ($response === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent successfully.'
            ], 200);
        }

        // If password reset link was not sent successfully, throw validation exception
        throw ValidationException::withMessages([
            'email' => [__($response)],
        ]);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reset(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        // Attempt to reset the user's password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update the user's password
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        // Check if the password was successfully reset
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        // Throw validation exception if password reset failed
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}

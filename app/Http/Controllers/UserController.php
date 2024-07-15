<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Get the authenticated user's information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showAuthenticatedUser()
    {
        // Get the authenticated user's information
        $user = Auth::user();

        // Return the user's information as a JSON response
        return response()->json($user);
    }

    /**
     * Update the specified user's personal information in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePersonalInformation(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'first_name' => 'nullable|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'place_of_birth' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female',
                'email' => 'nullable|string|max:255|email|unique:users,email,' . Auth::id() . ',user_id',
                'username' => 'nullable|string|max:255|unique:users,username,' . Auth::id() . ',user_id',
                'recovery_email' => 'nullable|string|max:255|email|unique:users,recovery_email,' . Auth::id() . ',user_id',
                'phone_number' => 'nullable|string|max:13',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_number' => 'nullable|string|max:13',
            ]);

            // Get the authenticated user
            $user = Auth::user();
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->place_of_birth = $request->input('place_of_birth');
            $user->date_of_birth = $request->input('date_of_birth');
            $user->gender = $request->input('gender');
            $user->username = $request->input('username');
            $user->email = $request->input('email');

            // Update user information based on request inputs
            if ($request->has('middle_name')) {
                $user->middle_name = $request->input('middle_name');
            }
            if ($request->has('recovery_email')) {
                $user->recovery_email = $request->input('recovery_email');
            }
            if ($request->has('phone_number')) {
                $user->phone_number = $request->input('phone_number');
            }
            if ($request->has('emergency_contact_name')) {
                $user->emergency_contact_name = $request->input('emergency_contact_name');
            }
            if ($request->has('emergency_contact_number')) {
                $user->emergency_contact_number = $request->input('emergency_contact_number');
            }

            // Save the user model
            $user->save();

            // Return a success response with the updated user data
            return response()->json(['message' => 'Personal information updated successfully', 'user' => $user]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to update personal information', ['error' => $e->getMessage()]);

            // Return an error response
            return response()->json(['error' => 'Failed to update personal information', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Change the password for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function changePassword(Request $request)
    {
        // Validate the request data
        $request->validate([
            'old_password' => 'required|string|max:255',
            'new_password' => 'required|string|min:8|max:255|confirmed',
        ]);

        try {
            // Get the current authenticated user
            $user = Auth::user();

            // Check if the old password matches the current password
            if (!Hash::check($request->input('old_password'), $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The old password does not match our records.'
                ], 422);
            }

            // Hash the new password and update it in the database
            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            // Return a success response
            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully.'
            ], 200);
        } catch (Exception $e) {
            // Return an error response in case of an exception
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while changing the password. Please try again.'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // Get the authenticated user
        $user = Auth::user();

        // Update user information based on request inputs
        if ($request->has('first_name')) {
            $user->first_name = $request->input('first_name');
        }
        if ($request->has('middle_name')) {
            $user->middle_name = $request->input('middle_name');
        }
        if ($request->has('last_name')) {
            $user->last_name = $request->input('last_name');
        }
        if ($request->has('place_of_birth')) {
            $user->place_of_birth = $request->input('place_of_birth');
        }
        if ($request->has('date_of_birth')) {
            $user->date_of_birth = $request->input('date_of_birth');
        }
        if ($request->has('gender')) {
            $user->gender = $request->input('gender');
        }
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }
        if ($request->has('username')) {
            $user->username = $request->input('username');
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
        if ($request->has('emergency_contact_phone_number')) {
            $user->emergency_contact_phone_number = $request->input('emergency_contact_phone_number');
        }

        // Save the user model
        $user->save();

        // Return a success response
        return response()->json(['message' => 'Personal information updated successfully']);
    }
}

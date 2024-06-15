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
        // Validate the incoming request data
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
        ]);

        // Update the authenticated user's information
        $user = Auth::user();
        $user->first_name = $request->first_name;
        $user->middle_name = $request->middle_name;
        $user->last_name = $request->last_name;
        $user->place_of_birth = $request->place_of_birth;
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender;
        $user->save();

        // Return a success response
        return response()->json(['message' => 'Personal information updated successfully']);
    }
}

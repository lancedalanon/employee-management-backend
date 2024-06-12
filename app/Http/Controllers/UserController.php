<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * Update the specified user's personal information in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatePersonalInformation(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'middle_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'last_name' => ['required', 'string', 'min:1', 'max:255'],
            'place_of_birth' => ['required', 'string', 'min:1', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:male,female'],
        ]);

        try {
            // Decode the token to extract the user ID
            $payload = JWTAuth::manager()->decode(JWTAuth::getToken(true));
            $userId = $payload['sub']; // Assuming user ID is stored as 'sub' in the payload

            // Find the user by ID
            $user = User::findOrFail($userId);

            // Update the user's information based on the provided data
            $user->update($validatedData);

            // Return a success response
            return response()->json(['message' => 'User information updated successfully'], 200);
        } catch (\Exception $e) {
            // Return an error response if something goes wrong
            return response()->json(['message' => 'Failed to update user information', 'error' => $e->getMessage()], 500);
        }
    }
}

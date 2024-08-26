<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UserController\UpdateContactInformationRequest;
use App\Http\Requests\v1\UserController\UpdatePasswordRequest;
use App\Http\Requests\v1\UserController\UpdatePersonalInformationRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected Authenticatable $user;

    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }

    public function show(): JsonResponse
    {
        // Fetch user information
        $user = User::select('first_name', 'middle_name', 'last_name', 'suffix', 
                            'place_of_birth', 'date_of_birth', 'gender', 'username', 
                            'email', 'recovery_email', 'phone_number', 'emergency_contact_name', 
                            'emergency_contact_number')
                    ->where('user_id', $this->user->user_id)
                    ->first();

        // Return the user information as a JSON response with success message
        $responseData = [
            'message' => 'User information retrieved successfully.',
            'data' => $user,
        ];

        // Return the response data
        return response()->json($responseData, 200);
    }

    public function updatePersonalInformation(UpdatePersonalInformationRequest $request): JsonResponse
    {
        // Retrieve validated data
        $validatedData = $request->validated();

        // Retrieve the current user
        $user = User::where('user_id', $this->user->user_id)->first();

        // Handle case where user is not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Define the fields that should be checked and updated
        $fieldsToCheck = [
            'first_name', 
            'middle_name', 
            'last_name', 
            'suffix', 
            'place_of_birth', 
            'date_of_birth', 
            'gender'
        ];

        // Loop through each field to check for changes
        $changesMade = false;
        foreach ($fieldsToCheck as $field) {
            if (isset($validatedData[$field]) && $validatedData[$field] !== $user->$field) {
                // Update the field if the request data is different from the current data
                $user->$field = $validatedData[$field];
                $changesMade = true;
            }
        }

        // Save the user if any changes were made
        if ($changesMade) {
            $user->save();
            $message = 'Personal information updated successfully.';
        } else {
            $message = 'No changes detected.';
        }

        // Prepare the response data
        $responseData = [
            'message' => $message,
            'data' => $user,
        ];

        // Return the response
        return response()->json($responseData, 200);
    }

    public function updateContactInformation(UpdateContactInformationRequest $request): JsonResponse
    {
        // Retrieve validated data
        $validatedData = $request->validated();

        // Retrieve the current user
        $user = User::where('user_id', $this->user->user_id)->first();

        // Handle case where user is not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Define the fields that should be checked and updated
        $fieldsToCheck = [
            'username', 
            'email', 
            'recovery_email', 
            'phone_number', 
            'emergency_contact_name', 
            'emergency_contact_number',
        ];

        // Loop through each field to check for changes
        $changesMade = false;
        foreach ($fieldsToCheck as $field) {
            if (isset($validatedData[$field]) && $validatedData[$field] !== $user->$field) {
                // Update the field if the request data is different from the current data
                $user->$field = $validatedData[$field];
                $changesMade = true;
            }
        }

        // Save the user if any changes were made
        if ($changesMade) {
            $user->save();
            $message = 'Contact information updated successfully.';
        } else {
            $message = 'No changes detected.';
        }

        // Prepare the response data
        $responseData = [
            'message' => $message,
            'data' => $user,
        ];

        // Return the response
        return response()->json($responseData, 200);
    }

    public function updateApiKey(Request $request): JsonResponse
    {
        // Retrieve the API key from the X-API-Key header
        $apiKey = $request->header('X-API-Key');

        // Validate the presence of the API key
        if (!$apiKey) {
            return response()->json(['message' => 'API key is required.'], 400);
        }

        // Validate the API key validity
        if (strlen($apiKey) < 32 || strlen($apiKey) > 500) {
            return response()->json(['message' => 'Invalid API key format.'], 422);
        }

        // Retrieve the current user
        $user = User::where('user_id', $this->user->user_id)->first();

        // Handle case where user is not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Encrypt the API key before storing it
        $encryptedApiKey = Crypt::encryptString($apiKey);

        // Update the user's API key
        $user->api_key = $encryptedApiKey;
        $user->save();

        // Return a success response
        return response()->json(['message' => 'API key updated successfully.'], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        // Retrieve validated data
        $validatedData = $request->validated();

        // Retrieve the currently authenticated user
        $user = User::where('user_id', $this->user->user_id)->first();
    
        // Handle case where user is not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check if the old password matches the current password
        if (!Hash::check($validatedData['old_password'], $user->password)) {
            return response()->json(['message' => 'The old password is incorrect.'], 400);
        }
    
        // Check if the new password is the same as the old password
        if (Hash::check($validatedData['new_password'], $user->password)) {
            return response()->json(['message' => 'The new password cannot be the same as the old password.'], 400);
        }
    
        // Hash the new password
        $user->password = Hash::make($validatedData['new_password']);
    
        // Save the new password
        $user->save();
    
        // Return a success response
        return response()->json(['message' => 'Password updated successfully.'], 200);
    }
}

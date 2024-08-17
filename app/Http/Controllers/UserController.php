<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\InviteToken;
use App\Models\User;
use App\Notifications\InviteNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class UserController extends Controller
{
    /**
     * Get the authenticated user's information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        // Get the authenticated user's information
        $user = Auth::user();

        // Return the user's information as a JSON response
        return response()->json([
            'message' => 'Personal information retrieved successfully',
            'data' => $user,
        ], 200);
    }

    public function register(Request $request, $token) {

    }

    public function registerCompanyAdmin(Request $request) {
        $validatedData = $request->validate([
            // Company Admin
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:13|unique:users,phone_number',
            'password' => 'required|string|min:8|max:255|confirmed',

            // Company
            'company_name' => 'required|string|unique:companies,company_name|max:255',
            'company_registration_number' => 'required|string|unique:companies,company_registration_number|max:50',
            'company_tax_id' => 'required|string|unique:companies,company_tax_id|max:50',
            'company_address' => 'required|string|max:255',
            'company_city' => 'required|string|max:100',
            'company_state' => 'required|string|max:100',
            'company_postal_code' => 'required|string|max:20',
            'company_country' => 'required|string|max:100',
            'company_phone_number' => 'required|string|unique:companies,company_phone_number|max:20',
            'company_email' => 'required|email|unique:companies,company_email|max:255',
            'company_website' => 'required|url|max:255',
            'company_industry' => 'required|string|max:100',
            'company_founded_at' => 'required|date',
            'company_description' => 'nullable|string',
        ]);

        // Create the user (Company Admin)
        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'] ?? null,
            'last_name' => $validatedData['last_name'],
            'suffix' => $validatedData['suffix'] ?? null,
            'place_of_birth' => $validatedData['place_of_birth'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'gender' => $validatedData['gender'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // Create the company
        $company = Company::create([
            'user_id' => $user->user_id,
            'company_name' => $validatedData['company_name'],
            'company_registration_number' => $validatedData['company_registration_number'],
            'company_tax_id' => $validatedData['company_tax_id'],
            'company_address' => $validatedData['company_address'],
            'company_city' => $validatedData['company_city'],
            'company_state' => $validatedData['company_state'],
            'company_postal_code' => $validatedData['company_postal_code'],
            'company_country' => $validatedData['company_country'],
            'company_phone_number' => $validatedData['company_phone_number'],
            'company_email' => $validatedData['company_email'],
            'company_website' => $validatedData['company_website'],
            'company_industry' => $validatedData['company_industry'],
            'company_founded_at' => $validatedData['company_founded_at'],
            'company_description' => $validatedData['company_description'] ?? null,
        ]);

        // Update the user with the company_id
        $user->company_id = $company->company_id;
        $user->assignRole('company-admin');
        $user->save();

        // Return a response indicating success
        return response()->json([
            'message' => 'Company Admin and Company registered successfully.',
        ], 201);
    }

    public function sendInvite(Request $request) {
        // Validate the request
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Fetch the company_id from the authenticated user
        $company_id = $user->company_id;

        if (!$company_id) {
            return response()->json(['message' => 'User does not belong to a company.'], 403);
        }

        // Generate a unique token and set the expiration date
        $token = InviteToken::generateToken();
        $expiresAt = Carbon::now()->addHours(24); // Token expires in 24 hours

        // Create the invite
        InviteToken::create([
            'company_id' => $company_id,
            'email' => $request->input('email'),
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Send invite email
        Notification::route('mail', $request->input('email'))
                    ->notify(new InviteNotification($token));

        return response()->json(['message' => 'Invite sent successfully.'], 200);
    }

    /**
     * Update the specified user's personal information in the database.
     *
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
                'suffix' => 'nullable|string|max:255',
                'place_of_birth' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:Male,Female',
                'email' => 'nullable|string|max:255|email|unique:users,email,'.Auth::id().',user_id',
                'username' => 'nullable|string|max:255|unique:users,username,'.Auth::id().',user_id',
                'recovery_email' => 'nullable|string|max:255|email|unique:users,recovery_email,'.Auth::id().',user_id',
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
            return response()->json([
                'message' => 'Personal information updated successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            // Return an error response
            return response()->json([
                'message' => 'Failed to update personal information',
            ], 500);
        }
    }

    /**
     * Change the password for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
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
            if (! Hash::check($request->input('old_password'), $user->password)) {
                return response()->json([
                    'message' => 'The old password does not match our records.',
                ], 422);
            }

            // Hash the new password and update it in the database
            $user->password = Hash::make($request->input('new_password'));
            $user->save();

            // Return a success response
            return response()->json([
                'message' => 'Password changed successfully.',
            ], 200);
        } catch (Exception $e) {
            // Return an error response in case of an exception
            return response()->json([
                'message' => 'An error occurred while changing the password. Please try again.',
            ], 500);
        }
    }
}

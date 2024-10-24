<?php

namespace App\Services\v1;

use App\Models\Company;
use App\Models\InviteToken;
use App\Models\User;
use App\Notifications\InviteNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class RegistrationService
{
    public function register(array $validatedData): JsonResponse
    {
        // Check if the token exists and hasn't expired
        $inviteToken = InviteToken::where('token', $validatedData['token'])
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('used_at')
            ->first();
    
        if (!$inviteToken) {
            return response()->json(['message' => 'This token is invalid or has expired.'], 400);
        }
    
        // Validate if the email has already been registered
        $isEmailExists = User::where('email', $inviteToken->email)->exists();
    
        if ($isEmailExists) {
            return response()->json(['message' => 'This email is already in use.'], 400);
        }
    
        // Create the user (Company Admin)
        $user = User::create([
            'first_name' => strtoupper($validatedData['first_name']),
            'middle_name' => strtoupper($validatedData['middle_name']) ?? null,
            'last_name' => strtoupper($validatedData['last_name']),
            'suffix' => strtoupper($validatedData['suffix']) ?? null,
            'place_of_birth' => strtoupper($validatedData['place_of_birth']),
            'date_of_birth' => $validatedData['date_of_birth'],
            'gender' => $validatedData['gender'],
            'username' => $validatedData['username'],
            'email' => $inviteToken->email,
            'phone_number' => $validatedData['phone_number'],
            'password' => Hash::make($validatedData['password']),
            'company_id' => $inviteToken->company_id,
        ]);
    
        // Assign user appropriate employment type, shift, and role
        $user->assignRole($validatedData['employment_type']);
        $user->assignRole($validatedData['shift']);
        $user->assignRole($validatedData['role']);
    
        // Mark the token as used by setting the used_at timestamp
        $inviteToken->update(['used_at' => Carbon::now()]);
    
        // Log in the user with Sanctum
        if (Auth::attempt(['username' => $validatedData['username'], 'password' => $validatedData['password']])) {
            // Retrieve the authenticated user
            $user = Auth::user();
    
            // Generate a Sanctum token for the authenticated user
            $token = $user->createToken(config('app.token_name', 'auth_token'))->plainTextToken;
    
            // Prepare the success response data
            $success = [
                'user_id' => $user->user_id,
                'token' => $token,
                'roles' => $user->getRoleNames(),
            ];
    
            // Return a success response with token and roles
            return response()->json([
                'message' => 'Registration successful and user logged in.',
                'data' => $success,
            ], 201);
        }
    
        // In case authentication fails (unlikely in this case)
        return response()->json([
            'message' => 'Registration successful, but authentication failed.',
        ], 500);
    }
    
    public function registerCompanyAdmin(array $validatedData): JsonResponse
    {
        // Create the user (Company Admin)
        $user = User::create([
            'first_name' => strtoupper($validatedData['first_name']),
            'middle_name' => strtoupper($validatedData['middle_name']) ?? null,
            'last_name' => strtoupper($validatedData['last_name']),
            'suffix' => strtoupper($validatedData['suffix']) ?? null,
            'place_of_birth' => strtoupper($validatedData['place_of_birth']),
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
            'company_name' => strtoupper($validatedData['company_name']),
            'company_registration_number' => $validatedData['company_registration_number'],
            'company_tax_id' => $validatedData['company_tax_id'],
            'company_address' => strtoupper($validatedData['company_address']),
            'company_city' => strtoupper($validatedData['company_city']),
            'company_state' => strtoupper($validatedData['company_state']),
            'company_postal_code' => $validatedData['company_postal_code'],
            'company_country' => strtoupper($validatedData['company_country']),
            'company_phone_number' => $validatedData['company_phone_number'],
            'company_email' => $validatedData['company_email'],
            'company_website' => $validatedData['company_website'],
            'company_industry' => strtoupper($validatedData['company_industry']),
            'company_founded_at' => $validatedData['company_founded_at'],
            'company_description' => $validatedData['company_description'] ?? null,
        ]);
    
        // Update the user with the company_id
        $user->company_id = $company->company_id;
        $user->assignRole('company_admin');
        $user->assignRole('full_time');
        $user->assignRole('day_shift');
        $user->assignRole('employee');
        $user->save();
    
        // Attempt to authenticate the user using the provided credentials
        if (Auth::attempt(['username' => $validatedData['username'], 'password' => $validatedData['password']])) {
            // Retrieve the authenticated user
            $user = Auth::user();
    
            // Generate a Sanctum token for the authenticated user
            $token = $user->createToken(config('app.token_name', 'auth_token'))->plainTextToken;
    
            // Prepare the success response data
            $success = [
                'user_id' => $user->user_id,
                'token' => $token,
                'roles' => $user->getRoleNames(), 
            ];
    
            // Return a success response with HTTP status code 200
            return response()->json([
                'message' => 'Company Admin registered and logged in successfully.',
                'data' => $success,
            ], 201);
        }
    
        // In case authentication fails, return an error response (edge case)
        return response()->json([
            'message' => 'Invalid credentials.',
        ], 500);
    }    

    public function sendInvite(array $validatedData): JsonResponse
    {
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
            'email' => $validatedData['email'],
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Send invite email
        Notification::route('mail', $validatedData['email'])
                    ->notify(new InviteNotification($token));

        return response()->json(['message' => 'Invite sent successfully.'], 201);
    }
}
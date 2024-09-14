<?php

namespace App\Services\v1\Admin;

use App\Models\User;
use App\Notifications\ChangePasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class UserService
{
    public function getUsers(array $validatedData): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = User::with([
                        'company:company_id,user_id,company_name', 
                        'roles' => function ($query) {
                            $query->whereIn('name', ['admin', 'employee', 'intern', 'company_admin', 'company_supervisor'])
                                ->select('name');
                        }])
                    ->select('user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'phone_number');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('user_id', 'LIKE', "%$search%")
                    ->orWhere('first_name', 'LIKE', "%$search%")
                    ->orWhere('middle_name', 'LIKE', "%$search%")
                    ->orWhere('last_name', 'LIKE', "%$search%")
                    ->orWhere('suffix', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('phone_number', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $projects = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $projects->isEmpty() ? 'No users found for the provided criteria.' : 'Users retrieved successfully.',
            'data' => $projects,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getUserById(int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = User::with([
                    'company:company_id,user_id,company_name', 
                    'roles' => function ($query) {
                        $query->whereIn('name', ['admin', 'employee', 'intern', 'company_admin', 'company_supervisor'])
                            ->select('name');
                    }])
                    ->where('user_id', $userId)
                    ->select('user_id', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'phone_number')
                    ->first();

        // Handle User not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'User retrieved successfully.',
            'data' => $user,
        ], 200);
    }

    public function createUser(array $validatedData): JsonResponse
    {
        // Create a new user with the validated data, handling nullable fields automatically
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
            'password' => Hash::make($validatedData['password']),
            'company_id' => $validatedData['company_id'] ?? null,
        ]);

        $user->assignRole($validatedData['role']);

        if ($validatedData['role'] !== 'admin') {
            $user->assignRole($validatedData['employment_type']);
            $user->assignRole($validatedData['shift']);
        }

        // Return a successful response with the created user data
        return response()->json([
            'message' => 'User created successfully.',
        ], 201);
    }

    public function updateUser(array $validatedData, int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = User::where('user_id', $userId)
                ->first();
        
        // Handle User not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update the user attributes with validated data
        $user->fill($validatedData);

        // Check if user attributes have changed using isDirty()
        if (!$user->isDirty()) {
            return response()->json(['message' => 'No changes detected.'], 400);
        }

        // Proceed with saving changes if there are updates
        $user->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User updated successfully.'], 200);
    }

    public function deleteUser(int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = User::where('user_id', $userId)
            ->select('user_id')
            ->first();

        // Handle User not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Delete the user
        $user->delete();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User deleted successfully.'], 200);
    }

    public function changeUserRole(array $validatedData, int $userId): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();
    
        try {
            // Retrieve the User for the given ID and check if it exists
            $user = User::with(['roles'])
                        ->where('user_id', $userId)
                        ->first();
    
            // Handle User not found
            if (!$user) {
                DB::rollBack();
                return response()->json(['message' => 'User not found.'], 404);
            }
    
            // Get the existing roles of the user
            $originalRoles = $user->roles->pluck('name')->sort()->values()->toArray();
    
            // Assign new roles based on validated data
            $rolesToAssign = [];
            
            if (isset($validatedData['role'])) {
                $rolesToAssign[] = $validatedData['role'];
            }
            
            if (isset($validatedData['employment_type'])) {
                $rolesToAssign[] = $validatedData['employment_type'];
            }
    
            if (isset($validatedData['shift'])) {
                $rolesToAssign[] = $validatedData['shift'];
            }
    
            // Sync roles with the user
            $user->syncRoles($rolesToAssign);
    
            // Get the new roles of the user
            $newRoles = $user->roles->pluck('name')->sort()->values()->toArray();
    
            // Compare the original roles with the new roles
            if ($originalRoles === $newRoles) {
                DB::rollBack();
                return response()->json(['message' => 'No changes detected in user roles.'], 400);
            }
    
            // Commit the transaction if changes are made
            DB::commit();
    
            // Return a successful response
            return response()->json(['message' => 'User roles updated successfully.'], 200);
    
        } catch (\Exception $e) {
            // Rollback on exception
            DB::rollBack();
            // Return a server error response
            return response()->json(['message' => 'An error occurred while updating user roles.'], 500);
        }
    }    

    public function changeUserPassword(array $validatedData, int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = User::where('user_id', $userId)->first();
    
        // Handle User not found
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        // Hash the new password
        $hashedPassword = Hash::make($validatedData['password']);
    
        // Update the user password
        $user->update(['password' => $hashedPassword]);
    
        // Send a notification to the user with the new password
        Notification::route('mail', $user->email)
            ->notify(new ChangePasswordNotification($validatedData['password']));
    
        // Return a successful response
        return response()->json(['message' => 'Password updated successfully and notification sent.'], 200);
    }
}
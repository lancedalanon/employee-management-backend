<?php

namespace App\Services\v1\Admin;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        // Start a database transaction
        DB::beginTransaction();
        
        try {
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
            
            // Retrieve the original roles
            $originalRoles = $user->roles->pluck('name')->sort()->values()->toArray();
            
            // Update the user attributes with validated data
            $user->fill($validatedData);
            
            // Update roles if provided in $validatedData
            if (isset($validatedData['roles'])) {
                // Sync roles with provided data
                $user->roles()->sync($validatedData['roles']);
            }
            
            // Check if any fields have changed using isDirty()
            if (!$user->isDirty() && $originalRoles === $user->roles->pluck('name')->sort()->values()->toArray()) {
                DB::rollBack(); // Rollback transaction
                return response()->json(['message' => 'No changes detected.'], 400);
            }
            
            // Proceed with saving changes if there are updates
            $user->save();
            
            // Commit transaction
            DB::commit();
            
            // Return the response as JSON with a 200 status code
            return response()->json(['message' => 'User updated successfully.'], 200);
            
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();
            // Return error response
            return response()->json(['message' => 'Failed to update user.'], 500);
        }
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
}
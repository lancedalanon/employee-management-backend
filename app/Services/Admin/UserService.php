<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Response;

class UserService
{
    public function index(int $perPage, int $page)
    {
        // Retrieve users who have either 'intern' or 'employee' roles and eager load the roles.
        $users = User::with(['roles'])
            ->role(['intern', 'employee']) // Ensure we're querying users with 'intern' or 'employee' roles
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' and 'super' role
                $query->where('name', 'admin')
                    ->whereOr('name', 'super');
            })
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the users' collection to filter the roles and determine the primary role.
        $users->getCollection()->transform(function ($user) {
            // Filter roles to include only 'intern' or 'employee'.
            $filteredRoles = $user->roles->filter(function ($role) {
                return in_array($role->name, ['intern', 'employee']);
            });

            // Determine the primary role (e.g., if multiple roles exist, prioritize one).
            $user->role = $filteredRoles->pluck('name')->first() ?? 'user';

            // Optionally, remove the original 'roles' attribute if not needed.
            unset($user->roles);

            return $user;
        });

        // Return the paginated users along with their filtered roles in your preferred format (e.g., as JSON).
        return Response::json([
            'message' => 'Users retrieved successfully.',
            'current_page' => $users->currentPage(),
            'data' => $users->items(),
            'first_page_url' => $users->url(1),
            'from' => $users->firstItem(),
            'last_page' => $users->lastPage(),
            'last_page_url' => $users->url($users->lastPage()),
            'links' => $users->linkCollection()->toArray(),
            'next_page_url' => $users->nextPageUrl(),
            'path' => $users->path(),
            'per_page' => $users->perPage(),
            'prev_page_url' => $users->previousPageUrl(),
            'to' => $users->lastItem(),
            'total' => $users->total(),
        ], 200);
    }

    public function show(int $userId)
    {
        // Retrieve the user by their ID, including their roles.
        $user = User::with(['roles'])
            ->role(['intern', 'employee'])
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' and 'super' role
                $query->where('name', 'admin')
                    ->whereOr('name', 'super');
            })
            ->where('user_id', $userId)
            ->first();

        // If no matching role is found, you can choose to return a 404 or another appropriate response.
        if (! $user) {
            return Response::json([
                'message' => 'User not found.',
            ], 404);
        }

        // Filter the roles to include only 'intern' or 'employee'.
        $filteredRoles = $user->roles->filter(function ($role) {
            return in_array($role->name, ['intern', 'employee']);
        });

        // Determine the primary role (e.g., if multiple roles exist, prioritize one).
        $user->role = $filteredRoles->pluck('name')->first();

        // Optionally, remove the original 'roles' attribute if not needed.
        unset($user->roles);

        // Return the user data as JSON.
        return Response::json([
            'message' => 'User retrieved successfully.',
            'data' => $user,
        ], 200);
    }

    public function store(array $validatedData)
    {
        // Create the user
        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'],
            'last_name' => $validatedData['last_name'],
            'suffix' => $validatedData['suffix'],
            'place_of_birth' => $validatedData['place_of_birth'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'gender' => $validatedData['gender'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']), // Hash the password
        ]);

        // Assign the roles to the user
        $user->assignRole($validatedData['role']); // Assign 'employee' or 'intern'
        $user->assignRole($validatedData['employment_type']); // Assign 'full-time' or 'part-time'
        $user->assignRole($validatedData['shift']); // Assign the work shift role

        // Return the created user data
        return Response::json([
            'message' => 'User created successfully.',
        ], 200);
    }

    public function update(array $validatedData, int $userId)
    {
        // Find the user by ID
        $user = User::role(['intern', 'employee'])
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' and 'super' role
                $query->where('name', 'admin')
                    ->whereOr('name', 'super');
            })
            ->where('user_id', $userId)
            ->first();

        // If user not found, return a 404 response
        if (! $user) {
            return Response::json([
                'message' => 'User not found.',
            ], 404);
        }

        // Update user details
        $user->update([
            'first_name' => $validatedData['first_name'],
            'middle_name' => $validatedData['middle_name'],
            'last_name' => $validatedData['last_name'],
            'suffix' => $validatedData['suffix'],
            'place_of_birth' => $validatedData['place_of_birth'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'gender' => $validatedData['gender'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'],
            'password' => isset($validatedData['password']) ? bcrypt($validatedData['password']) : $user->password, // Update password if provided
        ]);

        // Clear existing roles
        $user->syncRoles([]);

        // Assign new roles to the user if provided
        if (isset($validatedData['role'])) {
            $user->assignRole($validatedData['role']); // Assign 'employee' or 'intern'
        }

        if (isset($validatedData['employment_type'])) {
            $user->assignRole($validatedData['employment_type']); // Assign 'full-time' or 'part-time'
        }

        if (isset($validatedData['shift'])) {
            $user->assignRole($validatedData['shift']); // Assign the work shift role
        }

        // Return the updated user data
        return Response::json([
            'message' => 'User updated successfully.',
        ], 200);
    }

    public function destroy(int $userId)
    {
        // Find the user by ID
        $user = User::role(['intern', 'employee'])
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' and 'super' role
                $query->where('name', 'admin')
                    ->whereOr('name', 'super');
            })
            ->where('user_id', $userId)
            ->first();

        // If user not found, return a 404 response
        if (! $user) {
            return Response::json([
                'message' => 'User not found.',
            ], 404);
        }

        // Perform a soft delete
        $user->delete();

        // Return a success response
        return Response::json([
            'message' => 'User successfully deleted.',
        ], 200);
    }
}

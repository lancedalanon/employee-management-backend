<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request) 
    {
        // Get pagination parameters from the request, defaulting to 10 per page and page 1.
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Retrieve users who have either 'intern' or 'employee' roles and eager load the roles.
        $users = User::with(['roles'])
            ->role(['intern', 'employee']) // Ensure we're querying users with 'intern' or 'employee' roles
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' role
                $query->where('name', 'admin');
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
                // Exclude users who have the 'admin' role
                $query->where('name', 'admin');
            })
            ->where('user_id', $userId)
            ->first();

        // If no matching role is found, you can choose to return a 404 or another appropriate response.
        if (!$user) {
            return Response::json([
                'message' => 'User not found.'
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
            'data' => $user
        ], 200);    
    }    

    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:employee,intern', // Ensure only 'employee' or 'intern'
            'employment_type' => 'required|string|in:full-time,part-time', // Employment type
            'shift' => 'required|string|in:day-shift,afternoon-shift,evening-shift,early-shift,late-shift', // Work shift
        ]);
    
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

    public function update(Request $request, int $userId)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId, 'user_id')
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'user_id')
            ],
            'password' => 'nullable|string|min:8|confirmed', // Password is optional for update
            'role' => 'nullable|string|in:employee,intern', // Ensure only 'employee' or 'intern'
            'employment_type' => 'nullable|string|in:full-time,part-time', // Employment type
            'shift' => 'nullable|string|in:day-shift,afternoon-shift,evening-shift,early-shift,late-shift', // Work shift
        ]);

        // Find the user by ID
        $user = User::role(['intern', 'employee']) 
            ->whereDoesntHave('roles', function ($query) {
                // Exclude users who have the 'admin' role
                $query->where('name', 'admin');
            })
            ->where('user_id', $userId)
            ->first();
            
        // If user not found, return a 404 response
        if (!$user) {
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
                // Exclude users who have the 'admin' role
                $query->where('name', 'admin');
            })
            ->where('user_id', $userId)
            ->first();

        // If user not found, return a 404 response
        if (!$user) {
            return Response::json([
                'message' => 'User not found.',
            ], 404);
        }
    
        // Perform a soft delete
        $user->delete();
    
        // Return a success response
        return Response::json([
            'message' => 'User successfully deleted.'
        ], 200);
    }    
}

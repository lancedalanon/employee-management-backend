<?php

namespace App\Services\v1\CompanyAdmin;

use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProjectUserService
{
    public function getProjectUsers(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort'];
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Retrieve project users based on the given project ID and parameters
        $query = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                    ->where('project_id', $projectId)
                    ->where('company_id', $user->company_id)
                    ->select(['user_id', 'project_role']);

        // Apply search filter if provided
        if ($search) {
            $words = explode(' ', $search);

            $query->whereHas('user', function ($query) use ($words) {
                foreach ($words as $word) {
                    $query->where(function ($query) use ($word) {
                        $query->where('username', 'LIKE', "%$word%")
                            ->orWhere('first_name', 'LIKE', "%$word%")
                            ->orWhere('middle_name', 'LIKE', "%$word%")
                            ->orWhere('last_name', 'LIKE', "%$word%")
                            ->orWhere('suffix', 'LIKE', "%$word%");
                    });
                }
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data to exclude 'user_id' from the 'user' relation
        $transformedUsers = $users->map(function ($projectUser) {
            return [
                'user_id' => $projectUser->user_id,
                'project_role' => $projectUser->project_role,
                'username' => $projectUser->user->username,
                'full_name' => $projectUser->user->full_name,
            ];
        });

        // Replace the original collection with the transformed one
        $users->setCollection($transformedUsers);

        // Construct the response data
        $responseData = [
            'message' => $transformedUsers->isEmpty() ? 'No project users found for the provided criteria.' : 'Project users retrieved successfully.',
            'data' => $users, // Return the paginated result
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getProjectUsersById(Authenticatable $user, int $projectId, int $userId): JsonResponse
    {
        // Retrieve the User for the given ID and check if it exists
        $user = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                ->where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('company_id', $user->company_id)
                ->select(['user_id', 'project_role'])
                ->first();

        // Handle Project User not found
        if (!$user) {
            return response()->json(['message' => 'Project user not found.'], 404);
        }

        // Format the data to include the necessary fields
        $formattedUser = [
            'user_id' => $user->user_id,
            'project_role' => $user->project_role,
            'username' => $user->user->username,
            'full_name' => $user->user->full_name,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Project user retrieved successfully.',
            'data' => $formattedUser,
        ], 200);
    }

    public function bulkAddUsers(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Use a transaction to ensure all or nothing
        DB::beginTransaction();

        // Retrieve the authenticated user's company ID
        $companyId = $user->company_id;

        try {
            // Insert each project ID manually into the project_users table
            foreach ($validatedData['user_ids'] as $userId) {

                // Check if the user exists in the company
                $user = User::where('user_id', $userId)
                        ->where('company_id', $companyId)
                        ->exists();

                // Handle user not found in the company
                if (!$user) {
                    // Rollback the transaction if there was an error
                    DB::rollBack();

                    // Return a success response
                    return response()->json([
                        'message' => 'A user does not belong into the company or does not exist.',
                        'data' => ['user_id' => (int) $userId],
                    ], 404);
                }

                // Check if the user is already assigned to the project
                $existingUser = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                                ->where('user_id', $userId)
                                ->where('project_id', $projectId)
                                ->where('company_id', $companyId)
                                ->first();
                
                // Handle user already assigned to the project
                if ($existingUser) {
                    // Rollback the transaction if there was an error
                    DB::rollBack();

                    // Format the existing user data to include the necessary fields
                    $existingUserData = [
                        'user_id' => $existingUser->user->user_id,
                        'username' => $existingUser->user->username,
                        'first_name' => $existingUser->user->first_name,
                        'middle_name' => $existingUser->user->middle_name,
                        'last_name' => $existingUser->user->last_name,
                        'suffix' => $existingUser->user->suffix,
                        'full_name' => $existingUser->user->full_name,
                    ];

                    // Return a success response
                    return response()->json([
                        'message' => 'User is already assigned to the project.',
                        'data' => $existingUserData
                    ], 409);
                }

                // Insert the user into the project_users table
                DB::table('project_users')->insert([
                    'user_id' => $userId,
                    'project_id' => $projectId,
                    'company_id' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Commit the transaction if all is well
            DB::commit();

            // Return a success response
            return response()->json([
                'message' => 'Users assigned to projects successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction if there was an error
            DB::rollBack();

            // Return an error response
            return response()->json([
                'message' => 'Failed to assign users to project.', 
            ], 500);
        }
    }

    public function bulkRemoveUsers(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Start a database transaction
        DB::beginTransaction();

        // Retrieve the authenticated user's company ID
        $companyId = $user->company_id;

        try {
            // Iterate over each user ID in the validated data
            foreach ($validatedData['user_ids'] as $userId) {
                // Check if the user exists within the project
                $existingUser = ProjectUser::with(['user:user_id,username,first_name,middle_name,last_name,suffix'])
                                ->where('user_id', $userId)
                                ->where('project_id', $projectId)
                                ->where('company_id', $companyId)
                                ->first();

                // If the user does not exist, return a 404 response
                if (!$existingUser) {
                    return response()->json([
                        'message' => 'User does not exist in the project.',
                        'data' => ['user_id' => (int) $userId],
                    ], 404);
                }

                // Delete the user from the project_users table
                ProjectUser::where('user_id', $userId)
                        ->where('project_id', $projectId)
                        ->where('company_id', $companyId)
                        ->delete();
            }

            // Commit the transaction
            DB::commit();

            // Return a success response
            return response()->json([
                'message' => 'Users removed from project successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();

            // Return an error response
            return response()->json([
                'message' => 'Failed to remove users from project.',
            ], 500);
        }
    }
}
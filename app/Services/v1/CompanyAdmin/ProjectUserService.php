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
        DB::beginTransaction();

        $companyId = $user->company_id;

        try {
            // Validate user_ids
            $userIds = $validatedData['user_ids'];
            if (!is_array($userIds) || empty($userIds)) {
                return response()->json([
                    'message' => 'Invalid user IDs provided.',
                ], 400);
            }

            // Fetch users with necessary details
            $users = User::with(['projects' => function ($query) use ($projectId) {
                $query->select('project_users.project_id', 'project_users.user_id', 'project_users.deleted_at')
                    ->where('project_users.project_id', $projectId)
                    ->withPivot('deleted_at');
            }])
            ->where('company_id', $companyId)
            ->whereIn('user_id', $userIds)
            ->select('users.user_id', 'users.username', 'users.first_name', 'users.middle_name', 'users.last_name', 'users.suffix')
            ->get();

            $usersAlreadyInProject = [];
            $usersDeletedFromProject = [];
            $newUsers = [];

            foreach ($users as $user) {
                $hasProject = false;
                foreach ($user->projects as $project) {
                    $hasProject = true;
                    if ($project->pivot->deleted_at === null) {
                        // User is already associated with the project
                        $usersAlreadyInProject[] = $user;
                    } else {
                        // Existing project with deleted_at not null
                        $usersDeletedFromProject[] = $user;
                    }
                }
                if (!$hasProject) {
                    $newUsers[] = $user;
                }
            }

            // Check if there are users already in the project
            if (count($usersAlreadyInProject) > 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Some users are already associated with the project and cannot be added again.',
                    'data' => $usersAlreadyInProject
                ], 400);
            }

            // Restore users with deleted_at in their pivot
            if (count($usersDeletedFromProject) > 0) {
                foreach ($usersDeletedFromProject as $user) {
                    $user->projects()->updateExistingPivot($projectId, [
                        'deleted_at' => null,
                        'updated_at' => now()
                    ]);
                }
            }

            // Add new users to the project
            if (count($newUsers) > 0) {
                foreach ($newUsers as $user) {
                    $user->projects()->attach($projectId, [
                        'company_id' => $companyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Users assigned to projects successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

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
                $existingUser = ProjectUser::where('user_id', $userId)
                                ->where('project_id', $projectId)
                                ->where('company_id', $companyId)
                                ->exists();

                // If the user does not exist, return a 404 response
                if (!$existingUser) {
                    DB::rollBack();

                    // Fetch the user information
                    $user = User::select('user_id', 'first_name', 'middle_name', 
                                'last_name', 'suffix')
                            ->where('user_id', $userId)
                            ->first();

                    return response()->json([
                        'message' => 'User does not exist in the project.',
                        'data' => $user
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

    public function changeRole(Authenticatable $user, int $projectId, int $userId): JsonResponse
    {
        // Get user admin company_id
        $companyId = $user->company_id;

        // Retrieve the user from the given ID and check if it exists
        $user = ProjectUser::where('user_id', $userId)
                ->where('project_id', $projectId)
                ->where('company_id', $companyId)
                ->whereHas('project.users', function ($query) use ($userId, $companyId) {
                    $query->where('users.user_id', $userId)
                        ->where('users.company_id', $companyId);
                })
                ->first();
        
        return response()->json($user, 200);
    }
}
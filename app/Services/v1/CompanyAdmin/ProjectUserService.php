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
            // Fetch users with necessary details
            $users = User::with(['projects' => function ($query) use ($projectId) {
                $query->select('project_users.project_id', 'project_users.user_id', 'project_users.deleted_at')
                    ->where('project_users.project_id', $projectId)
                    ->withPivot('deleted_at');
            }])
            ->where('company_id', $companyId)
            ->whereIn('user_id', $validatedData['user_ids'])
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
        DB::beginTransaction();
    
        $companyId = $user->company_id;
    
        try {
            // Fetch users with their project details
            $users = User::with(['projects' => function ($query) use ($projectId) {
                $query->select('project_users.project_id', 'project_users.user_id', 'project_users.deleted_at')
                    ->where('project_users.project_id', $projectId)
                    ->withPivot('deleted_at');
            }])
            ->where('company_id', $companyId)
            ->whereIn('user_id', $validatedData['user_ids'])
            ->select('users.user_id', 'users.username', 'users.first_name', 'users.middle_name', 'users.last_name', 'users.suffix')
            ->get();

            $usersNotInProject = [];
            $usersRemovedFromProject = [];
            $usersInProject = [];
    
            foreach ($users as $user) {
                $isInProject = false;
                foreach ($user->projects as $project) {
                    if ($project->pivot->deleted_at === null) {
                        // User is associated with the project
                        $isInProject = true;
                        $usersInProject[] = $user; // Track users found in the project
                        break;
                    }
                }
    
                if ($isInProject) {
                    // Remove the user from the project
                    $user->projects()->updateExistingPivot($projectId, [
                        'deleted_at' => now(),
                        'updated_at' => now()
                    ]);
                    $usersRemovedFromProject[] = $user;
                } else {
                    // User is not part of the project
                    $usersNotInProject[] = $user;
                }
            }
    
            // Stop the operation if any user was not found in the project
            if (count($usersNotInProject) > 0) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Some users were not found in the specified project and thus were not removed.',
                    'data' => $usersNotInProject
                ], 400);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Users removed from the project successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'message' => 'Failed to remove users from the project.',
            ], 500);
        }
    }         

    public function changeRole(Authenticatable $user, array $validatedData, int $projectId, int $userId): JsonResponse
    {
        // Get user admin company_id
        $companyId = $user->company_id;

        // Retrieve the user from the given ID and check if it exists
        $user = ProjectUser::with(['user' => function ($query) use ($companyId) {
                        $query->select('user_id', 'username', 'first_name', 
                                        'middle_name', 'last_name', 'suffix')
                                ->where('company_id', $companyId);
                    }])
                    ->where('user_id', $userId)
                    ->whereNot('user_id', $user->user_id)
                    ->where('project_id', $projectId)
                    ->where('company_id', $companyId)
                    ->whereHas('user', function ($query) use ($userId, $companyId) {
                        $query->where('users.user_id', $userId)
                            ->where('users.company_id', $companyId);
                    })
                ->first();

        // Check if the user exists in the project
        if (!$user) {
            return response()->json([
                'message' => 'User not found in the specified project.'
            ], 404);
        }

        // Check if the user is already in the specified role in the project
        if ($user->project_role === $validatedData['project_role']) {
            return response()->json([
                'message' => 'User is already assigned to the specified role in this project.'
            ], 422);
        }

        // Update the user's role in the project
        $user->project_role = $validatedData['project_role'];
        $user->save();
        
        return response()->json([
            'message' => 'User role in the project updated successfully.', 
        ], 200);
    }
}
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectUser;
use Illuminate\Support\Facades\Response;
use App\Services\CacheService;

class ProjectUserService
{
    protected $defaultProjectRole;
    protected $validRoles;
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->defaultProjectRole = config('constants.project_roles.project-user');
        $this->validRoles = config('constants.project_roles');
        $this->cacheService = $cacheService;
    }

    public function indexUser(int $projectId, int $perPage, int $page)
    {
        try {
            $cacheKey = "project_users_projectId_{$projectId}_perPage_{$perPage}_page_{$page}";

            // Retrieve the project and eager load its users
            $project = Project::where('project_id', $projectId)
                ->first();

            // Handle project not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Extract users from the project
            $users = $this->cacheService->rememberForever($cacheKey, function () use ($perPage, $page, $project) {
                return $project->users()
                    ->paginate($perPage, ['*'], 'page', $page);
            });

            // Return the paginated project users as a JSON response
            return Response::json([
                'message' => 'Project users entries retrieved successfully.',
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
        } catch (\Exception $e) {
            // Handle exceptions (e.g., project not found)
            return Response::json([
                'message' => 'Failed to retrieve project users.',
            ], 500);
        }
    }

    public function showUser(int $projectId, int $userId)
    {
        try {
            // Cache the project and user data to improve performance
            $cacheKey = "project_user_projectId_{$projectId}_userId_{$userId}";

            // Retrieve the project and eager load its users
            $projectUser = $this->cacheService->rememberForever($cacheKey, function () use ($projectId, $userId) {
                return Project::with('users')
                    ->where('project_id', $projectId)
                    ->whereHas('users', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    })
                    ->first();
            });

            // Handle project user not found
            if (!$projectUser) {
                return Response::json([
                    'message' => 'Project user not found.',
                ], 404);
            }

            // Return the project user as a JSON response
            return Response::json([
                'message' => 'Project user has been retrieved successfully.',
                'data' => $projectUser,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions (e.g., project not found)
            return Response::json([
                'message' => 'Failed to retrieve project users.',
            ], 500);
        }
    }

    public function storeUser(array $validatedData, int $projectId)
    {
        try {
            $userIds = $validatedData['user_ids'];

            // Retrieve the project and eager load its users
            $project = Project::with('users')
                ->where('project_id', $projectId)
                ->first();

            // Handle project entry not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project entry not found.',
                ], 404);
            }

            // Ensure defaultProjectRole is not null
            if (is_null($this->defaultProjectRole)) {
                throw new \RuntimeException('Default project role is not configured.');
            }

            // Check if any users are already in the project
            $existingUserIds = $project->users()->pluck('users.user_id')->toArray();
            $usersToAdd = array_diff($userIds, $existingUserIds);

            // Handle already existing users
            if (count($usersToAdd) < count($userIds)) {
                $alreadyInProjectIds = array_diff($userIds, $usersToAdd);
                return Response::json([
                    'message' => 'Some users are already in the project.',
                    'data' => $alreadyInProjectIds,
                ], 409);
            }

            // Prepare sync data
            $syncData = array_fill_keys($usersToAdd, ['project_role' => $this->defaultProjectRole]);

            // Attach users to the project
            $project->users()->syncWithoutDetaching($syncData);

            // Fetch updated users
            $updatedUsers = $project->users()->whereIn('users.user_id', $usersToAdd)->get();

            // Return a success response
            return Response::json([
                'message' => 'Users added to project successfully with the specified role.',
                'data' => $updatedUsers,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while adding users to project.',
            ], 500);
        }
    }

    public function updateUser(array $validatedData, int $projectId)
    {
        try {
            $userId = $validatedData['user_id'];
            $role = $validatedData['project_role'];

            // Check if the role is valid
            if (!array_key_exists($role, $this->validRoles)) {
                return response()->json([
                    'message' => 'Invalid role provided.',
                ], 400);
            }

            // Check if the user is part of the project
            $projectUser = ProjectUser::where('project_id', $projectId)
                ->where('user_id', $userId)
                ->first();

            if (!$projectUser) {
                return response()->json([
                    'message' => 'User is not part of the project.',
                ], 400);
            }

            // Update the role for the user in the project
            $projectUser->project_role = $this->validRoles[$role];
            $projectUser->save();

            // Return a success response
            return response()->json([
                'message' => 'Project role updated successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json([
                'message' => 'An error occurred while updating project role.',
            ], 500);
        }
    }

    public function destroyUser(array $validatedData, int $projectId)
    {
        try {
            $userIds = $validatedData['user_ids'];

            // Retrieve the project
            $project = Project::where('project_id', $projectId)->first();

            // Handle project not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Fetch users to be removed
            $project->users()->whereIn('users.user_id', $userIds)->get();

            // Soft delete the relationship in the pivot table
            foreach ($userIds as $userId) {
                $project->users()->updateExistingPivot($userId, ['deleted_at' => now()]);
            }

            // Return a success response
            return response()->json([
                'message' => 'Users removed from project successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while removing users from project.',
            ], 500);
        }
    }
}

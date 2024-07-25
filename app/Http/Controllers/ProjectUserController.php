<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectUserController extends Controller
{
    /**
     * Retrieve users associated with the given project ID.
     *
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectUsers(Request $request, $projectId)
    {
        try {
            // Retrieve the project and eager load its users
            $project = Project::with('users')->findOrFail($projectId);

            // Extract users from the project
            $users = $project->users()->paginate(10);

            // Return a JSON response with the users
            return response()->json([
                'message' => 'Project users retrieved successfully.',
                'data' => $users
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Exception $e) {
            // Handle exceptions (e.g., project not found)
            return response()->json([
                'message' => 'Failed to retrieve project users.',
            ], 500);
        }
    }

    /**
     * Add users to a project.
     *
     * @param int $projectId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addUsersToProject(Request $request, $projectId)
    {
        $defaultProjectRole = config('constants.project_roles.project-user');

        try {
            // Validate the incoming request
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,user_id',
            ]);

            $userIds = $request->input('user_ids');

            // Attach users to the project with the specified role
            $project = Project::findOrFail($projectId);

            // Ensure defaultProjectRole is not null
            if (is_null($defaultProjectRole)) {
                throw new \RuntimeException('Default project role is not configured.');
            }

            // Check if any users are already in the project
            $existingUserIds = $project->users()->pluck('users.user_id')->toArray();
            $usersToAdd = array_diff($userIds, $existingUserIds);

            // Handle already existing users
            if (count($usersToAdd) < count($userIds)) {
                $alreadyInProjectIds = array_diff($userIds, $usersToAdd);
                return response()->json([
                    'message' => 'Some users are already in the project.',
                    'data' => $alreadyInProjectIds,
                ], 409);
            }

            // Prepare sync data
            $syncData = array_fill_keys($usersToAdd, ['project_role' => $defaultProjectRole]);

            // Attach users to the project
            $project->users()->syncWithoutDetaching($syncData);

            // Return a success response
            return response()->json([
                'message' => 'Users added to project successfully with the specified role.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while adding users to project.',
            ], 500);
        }
    }

    /**
     * Remove users from a project.
     *
     * @param int $projectId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeUsersFromProject(Request $request, $projectId)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,user_id',
            ]);

            $userIds = $request->input('user_ids');

            // Remove users from the project
            $project = Project::findOrFail($projectId);
            $project->users()->detach($userIds);

            // Return a success response
            return response()->json([
                'message' => 'Users removed from project successfully.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while removing users to project.',
            ], 500);
        }
    }

    /**
     * Update the project role of a single user in a project.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProjectRole(Request $request, $projectId)
    {
        // Define the available roles from the constants
        $validRoles = config('constants.project_roles');

        // Validate the incoming request
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'project_role' => 'required|in:' . implode(',', array_keys($validRoles)),
        ]);

        try {
            $userId = $request->input('user_id');
            $role = $request->input('project_role');

            // Check if the role is valid
            if (!array_key_exists($role, $validRoles)) {
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
            $projectUser->project_role = $validRoles[$role];
            $projectUser->save();

            // Return a success response
            return response()->json([
                'message' => 'Project role updated successfully.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle project not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation error.',
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json([
                'message' => 'An error occurred while updating project role.',
            ], 500);
        }
    }
}

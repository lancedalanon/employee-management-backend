<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectUserController extends Controller
{
    /**
     * Retrieve users associated with the given project ID.
     *
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectUsers($projectId)
    {
        try {
            // Retrieve the project and eager load its users
            $project = Project::with('users')->findOrFail($projectId);

            // Extract users from the project
            $users = $project->users;

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
    public function addUsersToProject($projectId, Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,user_id',
            ]);

            $userIds = $request->input('user_ids');
            $defaultProjectRole = 'project-user';

            // Attach users to the project with the specified role
            $project = Project::findOrFail($projectId);

            // Prepare data to sync without detaching
            $syncData = [];
            foreach ($userIds as $userId) {
                $syncData[$userId] = ['project_role' => $defaultProjectRole];
            }

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
    public function removeUsersFromProject($projectId, Request $request)
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
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectUserController extends Controller
{
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

            // Attach users to the project
            $project = Project::findOrFail($projectId);
            $project->users()->syncWithoutDetaching($userIds);

            // Return a success response
            return response()->json([
                'message' => 'Users added to project successfully.',
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

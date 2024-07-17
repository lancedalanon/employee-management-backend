<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects.
     *
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjects()
    {
        try {
            // Fetch paginated projects with only necessary user fields
            $projects = Project::with(['users' => function ($query) {
                $query->select('project_id', 'first_name', 'middle_name', 'last_name', 'username', 'project_id');
            }])->paginate(10);

            // Map the projects to include only necessary user fields
            $projects->getCollection()->transform(function ($project) {
                return [
                    'project_id' => $project->project_id,
                    'project_name' => $project->name,
                    'project_description' => $project->description,
                    'created_at' => $project->created_at,
                    'updated_at' => $project->updated_at,
                    'deleted_at' => $project->deleted_at,
                    'users' => $project->users->map(function ($user) {
                        return [
                            'user_id' => $user->id,
                            'full_name' => $user->full_name,
                            'username' => $user->username,
                        ];
                    })
                ];
            });

            // Return the paginated projects as a JSON response
            return response()->json($projects, 200);
        } catch (Exception $e) {
            // Return an error response
            return response()->json([
                'message' => 'An error occurred while fetching projects.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified project by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectsById($id)
    {
        try {
            // Fetch the project by ID
            $project = Project::with(['users'])->findOrFail($id);

            // Return the project as a JSON response
            return response()->json($project, 200);
        } catch (Exception $e) {
            // Check if the exception is a ModelNotFoundException
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'Project not found.'
                ], 404);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while fetching the project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new project.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProject(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string|max:500',
        ]);

        try {
            // Create a new project
            $project = Project::create($validatedData);

            // Return the created project as a JSON response
            return response()->json($project, 201);
        } catch (Exception $e) {
            // Return a validation error response
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while creating the project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified project's name by ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProject(Request $request, $id)
    {
        // Validate the incoming request
        $request->validate([
            'project_name' => 'required|string|max:255',
            'project_description' => 'nullable|string|max:500',
        ]);

        try {
            // Fetch the project by ID
            $project = Project::findOrFail($id);

            // Update the project's name
            $project->project_name = $request->input('project_name');
            $project->project_description = $request->input('project_description', '');
            $project->save();

            // Return the updated project as a JSON response
            return response()->json($project, 200);
        } catch (Exception $e) {
            // Check if the exception is a ModelNotFoundException
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'Project not found.'
                ], 404);
            }

            // Return a validation error response
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while updating the project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete the specified project by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteProject($id)
    {
        try {
            // Fetch the project by ID
            $project = Project::findOrFail($id);

            // Soft delete the project
            $project->delete();

            // Return a success response
            return response()->json(['message' => 'Project deleted successfully.'], 200);
        } catch (Exception $e) {
            // Check if the exception is a ModelNotFoundException
            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json([
                    'message' => 'Project not found.'
                ], 404);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while deleting the project.',
                'error' => $e->getMessage()
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

            // Attach users to the project
            $project = Project::findOrFail($projectId);
            $project->users()->syncWithoutDetaching($userIds);

            // Return a success response
            return response()->json([
                'message' => 'Users added to project successfully.',
            ], 200);
        } catch (Exception $e) {
            // Return a validation error response
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while adding users to project.',
                'error' => $e->getMessage()
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
        } catch (Exception $e) {
            // Return a validation error response
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Validation error.',
                    'errors' => $e->errors(),
                ], 422);
            }

            // Return a generic error response
            return response()->json([
                'message' => 'An error occurred while removing users from project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

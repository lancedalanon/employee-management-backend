<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // Fetch paginated projects with only necessary fields
            $projects = Project::with(['users' => function ($query) {
                $query->select('project_id', 'first_name', 'middle_name', 'last_name', 'username');
            }])->paginate(10);


            // Transform the projects to match the required JSON structure
            $transformedProjects = $projects->getCollection()->map(function ($project) {
                return [
                    'project_id' => $project->project_id,
                    'project_name' => $project->project_name,
                    'project_description' => $project->project_description,
                    'created_at' => $project->created_at->toIso8601String(),
                    'updated_at' => $project->updated_at->toIso8601String(),
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
            return response()->json([
                'message' => 'Project entries retrieved successfully.',
                'current_page' => $projects->currentPage(),
                'data' => $transformedProjects,
                'first_page_url' => $projects->url(1),
                'from' => $projects->firstItem(),
                'last_page' => $projects->lastPage(),
                'last_page_url' => $projects->url($projects->lastPage()),
                'links' => $projects->links(),
                'next_page_url' => $projects->nextPageUrl(),
                'path' => $projects->path(),
                'per_page' => $projects->perPage(),
                'prev_page_url' => $projects->previousPageUrl(),
                'to' => $projects->lastItem(),
                'total' => $projects->total(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching projects: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'An error occurred while fetching projects.',
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

            // Return the Project entry as a JSON response
            return response()->json([
                'message' => 'Project entry retrieved successfully.',
                'data' => $project
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while updating the project.',
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
            return response()->json([
                'message' => 'Project created successfully',
                'data' => $project,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error.',
            ], 422);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while creating the project.',
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

            if (!($request->input('project_name') !== $project->project_name)) {
                // Return a validation error response
                return response()->json([
                    'message' => 'Project name cannot be the same as the current name.'
                ], 422);
            }

            // Update the project's name
            $project->project_name = $request->input('project_name');
            $project->project_description = $request->input('project_description', '');
            $project->save();

            // Return the updated project as a JSON response
            return response()->json([
                'message' => 'Project updated successfully',
                'data' => $project,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while updating the project.',
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
            return response()->json([
                'message' => 'Project deleted successfully.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle DTR record not found
            return response()->json([
                'message' => 'Project not found.',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return response()->json([
                'message' => 'An error occurred while deleting the project.',
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

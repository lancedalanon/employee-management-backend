<?php

namespace App\Services\v1\CompanyAdmin;

use App\Models\Project;
use App\Models\ProjectUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectService
{
    public function getProjects(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = Project::select('project_id', 'project_name', 'project_description', 'created_at', 'updated_at')
                ->whereHas('users', function ($query) use ($user) {
                    $query->where('users.company_id', $user->company_id);
                });

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('project_name', 'LIKE', "%$search%")
                    ->orWhere('project_description', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $projects = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $projects->isEmpty() ? 'No projects found for the provided criteria.' : 'Projects retrieved successfully.',
            'data' => $projects,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getProjectById(Authenticatable $user, int $projectId): JsonResponse
    {
        // Retrieve the Project for the given ID and check if it exists
        $project = Project::whereHas('users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id);
                    })
                    ->where('project_id', $projectId)
                    ->first();

        // Handle Project not found
        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Project retrieved successfully.',
            'data' => $project,
        ], 200);
    }

    public function createProject(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Create the Project
        $project = Project::create([
            'project_name' => $validatedData['project_name'],
            'project_description' => $validatedData['project_description'] ?? null,
        ]);

        // Associate the Project with the current Company Admin
        ProjectUser::create([
            'project_id' => $project->project_id,
            'company_id' => $user->company_id,
            'user_id' => $user->user_id,
            'project_role' => 'project_admin',
        ]);

        // Return the response as JSON with a 201 status code
        return response()->json(['message' => 'Project created successfully.'], 201);
    }

    public function updateProject(Authenticatable $user, int $projectId, array $validatedData): JsonResponse
    {
        // Retrieve the Project for the given ID and check if it exists
        $project = Project::where('project_id', $projectId)
                    ->whereHas('users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id);
                    })
                    ->first();

        // Handle Project not found
        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        // Update the Project
        $project->update([
            'project_name' => $validatedData['project_name'],
            'project_description' => $validatedData['project_description']?? null,
        ]);

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Project updated successfully.'], 200);
    }

    public function deleteProject(Authenticatable $user, int $projectId): JsonResponse
    {
        // Retrieve the Project for the given ID and check if it exists
        $project = Project::where('project_id', $projectId)
                    ->whereHas('users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id);
                    })
                    ->first();

        // Handle Project not found
        if (!$project) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        // Delete the Project
        $project->delete();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Project deleted successfully.'], 200);
    }
}
<?php

namespace App\Services\v1;

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
                            $query->where('users.company_id', $user->company_id)
                                ->where('users.user_id', $user->user_id);
                        });

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('project_name', 'LIKE', "%$search%")
                    ->where('project_description', 'LIKE', "%$search%");
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
        $project = Project::select('project_id', 'project_name', 'project_description', 'created_at', 'updated_at')
                    ->whereHas('users', function ($query) use ($user) {
                        $query->where('users.user_id', $user->user_id)
                        ->where('users.company_id', $user->company_id);
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
}
<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectController\IndexRequest;
use App\Models\Project;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected Authenticatable $user;

    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }

    public function index(IndexRequest $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = Project::select('project_id', 'project_name', 'project_description', 'created_at', 'updated_at')
                        ->whereHas('users', function ($query) {
                            $query->where('users.company_id', $this->user->company_id)
                                ->where('users.user_id', $this->user->user_id);
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

    public function show($projectId): JsonResponse
    {
        // Retrieve the Project for the given ID and check if it exists
        $project = Project::select('project_id', 'project_name', 'project_description', 'created_at', 'updated_at')
                    ->whereHas('users', function ($query) {
                        $query->where('users.user_id', $this->user->user_id)
                        ->where('users.company_id', $this->user->company_id);
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

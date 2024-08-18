<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ProjectService
{
    protected $cacheService;

    protected $userId;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->userId = Auth::id();
    }

    public function index(int $perPage, int $page)
    {
        try {
            // Generate a cache key for the paginated projects based on the perPage and page parameters
            $cacheKey = "projects_userId_{$this->userId}_perPage_{$perPage}_page_{$page}";

            // Fetch paginated projects with only necessary fields
            $projects = $this->cacheService->rememberForever($cacheKey, function () use ($perPage, $page) {
                return Project::whereHas('users', function ($query) {
                    $query->where('users.user_id', $this->userId);
                })->paginate($perPage, ['*'], 'page', $page);
            });

            // Return the paginated projects as a JSON response
            return Response::json([
                'message' => 'Projects retrieved successfully.',
                'current_page' => $projects->currentPage(),
                'data' => $projects->items(),
                'first_page_url' => $projects->url(1),
                'from' => $projects->firstItem(),
                'last_page' => $projects->lastPage(),
                'last_page_url' => $projects->url($projects->lastPage()),
                'links' => $projects->linkCollection()->toArray(),
                'next_page_url' => $projects->nextPageUrl(),
                'path' => $projects->path(),
                'per_page' => $projects->perPage(),
                'prev_page_url' => $projects->previousPageUrl(),
                'to' => $projects->lastItem(),
                'total' => $projects->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return an error response
            return Response::json([
                'message' => 'An error occurred while fetching projects.',
            ], 500);
        }
    }

    public function show(int $projectId)
    {
        try {
            // Generate a cache key for the project based on the project ID
            $cacheKey = "project_userId_{$this->userId}_{$projectId}";

            // Fetch the project by ID
            $project = $this->cacheService->rememberForever($cacheKey, function () use ($projectId) {
                return Project::where('project_id', $projectId)
                    ->whereHas('users', function ($query) {
                        $query->where('users.user_id', $this->userId);
                    })
                    ->first();
            });

            // Check if the Project was found
            if (! $project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Return the Project as a JSON response
            return Response::json([
                'message' => 'Project retrieved successfully.',
                'data' => $project,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while fetching the project.',
            ], 500);
        }
    }

    public function store(array $validatedData)
    {
        try {
            // Create a new project
            $project = Project::create($validatedData);

            // Attach the authenticated user as a project member with the 'project_admin' role
            $project->users()->attach($this->userId, ['project_role' => 'project_admin']);

            // Return the created project as a JSON response
            return Response::json([
                'message' => 'Project created successfully.',
                'data' => $project,
            ], 201);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while creating the project.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId)
    {
        try {
            // Fetch the project by ID
            $project = Project::where('project_id', $projectId)->first();

            // Check if the Project was found
            if (! $project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            if (! ($validatedData['project_name'] !== $project->project_name)) {
                // Return a validation error response
                return Response::json([
                    'message' => 'Project name cannot be the same as the current name.',
                ], 422);
            }

            // Update the project's name
            $project->project_name = $validatedData['project_name'];
            $project->project_description = $validatedData['project_description'] ?? '';
            $project->save();

            // Return the updated project as a JSON response
            return Response::json([
                'message' => 'Project updated successfully',
                'data' => $project,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while updating the project.',
            ], 500);
        }
    }

    public function destroy(int $projectId)
    {
        try {
            // Fetch the project by ID
            $project = Project::where('project_id', $projectId)->first();

            // Check if the Project was found
            if (! $project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Soft delete the project
            $project->delete();

            // Return a success response
            return Response::json([
                'message' => 'Project deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'An error occurred while deleting the project.',
            ], 500);
        }
    }
}

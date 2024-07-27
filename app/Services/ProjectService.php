<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ProjectService
{
    public function index(int $perPage, int $page)
    {
        try {
            // Fetch paginated projects with only necessary fields
            $projects = Project::paginate($perPage, ['*'], 'page', $page);

            // Return the paginated projects as a JSON response
            return Response::json([
                'message' => 'Project entries retrieved successfully.',
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
            // Fetch the project by ID
            $project = Project::where('project_id', $projectId)
                ->first();

            // Check if the Project entry was found
            if (!$project) {
                return Response::json([
                    'message' => 'Project entry not found.'
                ], 404);
            }

            // Return the Project entry as a JSON response
            return Response::json([
                'message' => 'Project entry retrieved successfully.',
                'data' => $project
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

            // Return the created project as a JSON response
            return Response::json([
                'message' => 'Project created successfully',
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
            $project = Project::where('project_id', $projectId)
                ->first();

            // Check if the Project entry was found
            if (!$project) {
                return Response::json([
                    'message' => 'Project entry not found.'
                ], 404);
            }

            if (!($validatedData['project_name'] !== $project->project_name)) {
                // Return a validation error response
                return Response::json([
                    'message' => 'Project name cannot be the same as the current name.'
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
            $project = Project::where('project_id', $projectId)
                ->first();

            // Check if the Project entry was found
            if (!$project) {
                return Response::json([
                    'message' => 'Project entry not found.'
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

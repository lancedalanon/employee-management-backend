<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Response;

class ProjectTaskService
{
    public function index(int $perPage, int $page, int $projectId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Retrieve tasks for the given project ID, with pagination
            $tasks = ProjectTask::where('project_id', $project->project_id)
                ->paginate($perPage, ['*'], 'page', $page);

            // Handle case where tasks are not found
            if ($tasks->isEmpty()) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Return the ProjectTask as a JSON response
            return Response::json([
                'message' => 'Task retrieved successfully.',
                'current_page' =>  $tasks->currentPage(),
                'data' =>  $tasks->items(),
                'first_page_url' =>  $tasks->url(1),
                'from' =>  $tasks->firstItem(),
                'last_page' =>  $tasks->lastPage(),
                'last_page_url' =>  $tasks->url($tasks->lastPage()),
                'links' =>  $tasks->linkCollection()->toArray(),
                'next_page_url' =>  $tasks->nextPageUrl(),
                'path' =>  $tasks->path(),
                'per_page' =>  $tasks->perPage(),
                'prev_page_url' =>  $tasks->previousPageUrl(),
                'to' =>  $tasks->lastItem(),
                'total' =>  $tasks->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve task.',
            ], 500);
        }
    }

    public function show(int $projectId, int $taskId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Retrieve the specific task for the given project ID and task ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Return the specific ProjectTask as a JSON response
            return Response::json([
                'message' => 'Task retrieved successfully.',
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to retrieve task.',
            ], 500);
        }
    }

    public function store(array $validatedData, int $projectId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Create a new task for the given project ID
            $task = new ProjectTask([
                'project_task_name' => $validatedData['project_task_name'],
                'project_task_description' => $validatedData['project_task_description'],
                'project_task_progress' => $validatedData['project_task_progress'],
                'project_task_priority_level' => $validatedData['project_task_priority_level'],
                'project_id' => $project->project_id,
            ]);

            // Save the task to the database
            $task->save();

            // Return the newly created ProjectTask as a JSON response
            return Response::json([
                'message' => 'Task created successfully.',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to create task.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId,  int $taskId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)
                ->first();

            // Handle case where project is not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Find the task by its ID and project ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Update the task with validated data
            $task->project_task_name = $validatedData['project_task_name'];
            $task->project_task_description = $validatedData['project_task_description'];
            $task->project_task_progress = $validatedData['project_task_progress'];
            $task->project_task_priority_level = $validatedData['project_task_priority_level'];

            // Save the updated task to the database
            $task->save();

            // Return the updated ProjectTask as a JSON response
            return Response::json([
                'message' => 'Task updated successfully.',
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to update task.',
            ], 500);
        }
    }

    public function destroy(int $projectId, int $taskId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)
                ->first();

            // Handle case where project is not found
            if (!$project) {
                return Response::json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Find the task by its ID and project ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Soft delete the task
            $task->delete();

            // Return a JSON response indicating success
            return Response::json([
                'message' => 'Task deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to delete task.',
            ], 500);
        }
    }
}

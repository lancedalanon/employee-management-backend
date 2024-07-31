<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Support\Facades\Response;

class ProjectTaskSubtaskService
{
    public function index(int $perPage, int $page, int $projectId, int $taskId)
    {
        try {
            // Query the database to retrieve the paginated subtasks associated with the given project ID and task ID
            $subtasks = ProjectTaskSubtask::where('project_task_id', $taskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->paginate($perPage, ['*'], 'page', $page);

            // Return the specific ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtasks retrieved successfully.',
                'current_page' => $subtasks->currentPage(),
                'data' => $subtasks->items(),
                'first_page_url' => $subtasks->url(1),
                'from' => $subtasks->firstItem(),
                'last_page' => $subtasks->lastPage(),
                'last_page_url' => $subtasks->url($subtasks->lastPage()),
                'links' => $subtasks->linkCollection()->toArray(),
                'next_page_url' => $subtasks->nextPageUrl(),
                'path' => $subtasks->path(),
                'per_page' => $subtasks->perPage(),
                'prev_page_url' => $subtasks->previousPageUrl(),
                'to' => $subtasks->lastItem(),
                'total' => $subtasks->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve subtasks.',
            ], 500);
        }
    }

    public function show(int $projectId, int $taskId, int $subtaskId)
    {
        try {
            // Find the project task with the given project ID, task ID, and subtask ID
            $subtask = ProjectTaskSubtask::where('project_task_id', $taskId)
                ->where('project_task_subtask_id', $subtaskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Return a JSON response with the created subtask
            return Response::json([
                'message' => 'Subtask retrieved successfully.',
                'data' => $subtask,
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve subtask.',
            ], 500);
        }
    }

    public function store(array $validatedData, int $projectId, int $taskId)
    {
        try {
            // Find the project task with the given project ID and task ID
            $task = ProjectTask::where('project_id', $projectId)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where subtask is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Create a new task for the given project ID
            $subtask = new ProjectTask([
                'project_task_subtask_name' => $validatedData['project_task_subtask_name'],
                'project_task_subtask_description' => $validatedData['project_task_subtask_description'],
                'project_task_subtask_progress' => $validatedData['project_task_subtask_progress'],
                'project_task_subtask_priority_level' => $validatedData['project_task_subtask_priority_level'],
                'project_task_id' => $task->project_task_id,
            ]);

            // Save the subtask to the database
            $subtask->save();

            // Return the newly created ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtask created successfully.',
                'data' => $subtask
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to create subtask.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId, int $taskId, int $subtaskId)
    {
        try {
            // Find the project task with the given project ID, task ID, and subtask ID
            $subtask = ProjectTaskSubtask::where('project_task_id', $taskId)
                ->where('project_task_subtask_id', $subtaskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Update the subtask with validated data
            $subtask->project_task_subtask_name = $validatedData['project_task_subtask_name'];
            $subtask->project_task_subtask_description = $validatedData['project_task_subtask_description'];
            $subtask->project_task_subtask_progress = $validatedData['project_task_subtask_progress'];
            $subtask->project_task_subtask_priority_level = $validatedData['project_task_subtask_priority_level'];

            // Save the updated task to the database
            $subtask->save();

            // Return the updated ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtask updated successfully.',
                'data' => $subtask
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to update subtask.',
            ], 500);
        }
    }

    public function destroy(int $projectId, int $taskId, int $subtaskId)
    {
        try {
            // Find the project task with the given project ID, task ID, and subtask ID
            $subtask = ProjectTaskSubtask::where('project_task_id', $taskId)
                ->where('project_task_subtask_id', $subtaskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Soft delete the subtask
            $subtask->delete();

            // Return a JSON response indicating success
            return Response::json([
                'message' => 'Subtask deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to delete subtask.',
            ], 500);
        }
    }
}

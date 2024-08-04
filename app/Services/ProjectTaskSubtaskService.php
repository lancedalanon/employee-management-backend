<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class ProjectTaskSubtaskService
{
    protected $cacheService;
    protected $userId;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->userId = Auth::id();
    }

    public function index(int $perPage, int $page, int $projectId, int $taskId)
    {
        try {
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

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
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

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
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            // Create a new task for the given project ID
            $subtask = new ProjectTaskSubtask([
                'project_task_subtask_name' => $validatedData['project_task_subtask_name'],
                'project_task_subtask_description' => $validatedData['project_task_subtask_description'],
                'project_task_subtask_progress' => $validatedData['project_task_subtask_progress'],
                'project_task_subtask_priority_level' => $validatedData['project_task_subtask_priority_level'],
                'project_task_id' => $taskId,
            ]);

            // Save the subtask to the database
            $subtask->save();

            // Return the newly created ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtask created successfully.',
                'data' => $subtask
            ], 201);
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Failed to create subtask: ' . $e->getMessage(), [
                'projectId' => $projectId,
                'taskId' => $taskId,
                'validatedData' => $validatedData,
                'exception' => $e,
            ]);

            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to create subtask.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId, int $taskId, int $subtaskId)
    {
        try {
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            // Fetch the task by its ID and project ID
            $subtask = $this->isSubtaskExisting($projectId, $taskId, $subtaskId);

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
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            // Fetch the task by its ID and project ID
            $subtask = $this->isSubtaskExisting($projectId, $taskId, $subtaskId);

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

    protected function isSubTaskExisting(int $projectId, int $taskId, int $subtaskId)
    {
        // Fetch the subtask by its ID, project ID, and task ID, and subtask ID to see if it exists
        return ProjectTaskSubtask::where('project_task_id', $taskId)
            ->where('project_task_subtask_id', $subtaskId)
            ->whereHas('task', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->first();
    }

    protected function isUserAuthorized(int $projectId, int $taskId)
    {
        // Check if the user has permission to view the statuses for the given task
        return ProjectTask::where('project_task_id', $taskId)
            ->where('project_id', $projectId)
            ->whereHas('project.users', function ($query) {
                $query->where('users.user_id', $this->userId);
            })->exists();
    }
}

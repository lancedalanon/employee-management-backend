<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectUser;
use App\Models\User;
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
            $subtasks = ProjectTaskSubtask::where('project_task_id', $taskId)
                ->whereHas('task.project.users', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId)
                        ->where('users.user_id', $this->userId);
                })
                ->paginate($perPage, ['*'], 'page', $page);
        
            // Return the ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtasks retrieved successfully.',
                'current_page' =>  $subtasks->currentPage(),
                'data' =>  $subtasks->items(),
                'first_page_url' =>  $subtasks->url(1),
                'from' =>  $subtasks->firstItem(),
                'last_page' =>  $subtasks->lastPage(),
                'last_page_url' =>  $subtasks->url($subtasks->lastPage()),
                'links' =>  $subtasks->linkCollection()->toArray(),
                'next_page_url' =>  $subtasks->nextPageUrl(),
                'path' =>  $subtasks->path(),
                'per_page' =>  $subtasks->perPage(),
                'prev_page_url' =>  $subtasks->previousPageUrl(),
                'to' =>  $subtasks->lastItem(),
                'total' =>  $subtasks->total(),
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
            // Retrieve the specific task for the given project ID and task ID
            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId)
                            ->where('users.user_id', $this->userId);
                    })
                    ->first();

            // Check if the project task was found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.'
                ], 404);
            }

            // Return the specific ProjectTaskSubtask as a JSON response
            return Response::json([
                'message' => 'Subtask retrieved successfully.',
                'data' => $subtask
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
            // Check if the task exists
            $task = ProjectTask::where('project_task_id', $taskId)
                ->where('project_id', $projectId)
                ->whereHas('project.users', function ($query) {
                    $query->where('users.user_id', $this->userId);
                })
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Create a new subtask for the given project ID
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
            // Find the subtask by its ID and project ID and subtask ID
            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                ->where('project_task_id', $taskId)
                ->whereHas('task.project.users', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId)
                        ->where('users.user_id', $this->userId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Update the task with validated data
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
            // Find the subtask by its ID and project ID and subtask ID
            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                ->where('project_task_id', $taskId)
                ->whereHas('task.project.users', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId)
                        ->where('users.user_id', $this->userId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Soft delete the task
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

    public function addUser(int $projectId, int $taskId, int $subtaskId, int $userId)
    {
        try {
            if (!$this->isProjectAdmin($projectId) && !Auth::user()->hasRole('admin')) {
                return Response::json([
                   'message' => 'Forbidden.',
                ], 403);
            }

            $user = User::where('user_id', $userId)
                ->whereHas('projects.tasks.subtasks', function ($query) use ($projectId, $taskId, $subtaskId) {
                    $query->where('project_id', $projectId)
                        ->where('project_task_id', $taskId)
                        ->where('project_task_subtask_id', $subtaskId);
                })
                ->first();  
                
            if (!$user) {
                return Response::json([
                   'message' => 'User not found or not associated with the project.',
                ], 404);
            }

            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                ->where('project_task_id', $taskId)
                ->whereHas('task.project', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            if ($subtask->user_id === $userId)  {
                return Response::json([
                   'message' => 'User is already assigned to the subtask.',
                ], 409);
            }

            $subtask->user_id = $userId;
            $subtask->save();

            // Return a JSON response indicating success
            return Response::json([
               'message' => 'User assigned to subtask successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to assign user to subtask.',
            ], 500);
        }
    }

    public function removeUser(int $projectId, int $taskId, int $subtaskId, int $userId)
    {
        try {
            if (!$this->isProjectAdmin($projectId) && !Auth::user()->hasRole('admin')) {
                return Response::json([
                   'message' => 'Forbidden.',
                ], 403);
            }

            $user = User::where('user_id', $userId)
                ->whereHas('projects.tasks.subtasks', function ($query) use ($projectId, $taskId, $subtaskId) {
                    $query->where('project_id', $projectId)
                        ->where('project_task_id', $taskId)
                        ->where('project_task_subtask_id', $subtaskId);
                })
                ->first();  
                
            if (!$user) {
                return Response::json([
                   'message' => 'User not found or not associated with the project.',
                ], 404);
            }

            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                ->where('project_task_id', $taskId)
                ->whereHas('task.project', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            if ($subtask->user_id === null || $subtask->user_id !== $userId) {
                return Response::json([
                    'message' => 'User is not assigned to this subtask.',
                ], 409);
            }

            $subtask->user_id = null;
            $subtask->save();

            // Return a JSON response indicating success
            return Response::json([
               'message' => 'User removed from subtask successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to remove user from subtask.',
            ], 500);
        }
    }

    protected function isProjectAdmin(int $projectId) 
    {
        return ProjectUser::where('project_id', $projectId)
                ->where('user_id', $this->userId)
                ->where('project_role', 'project-admin')
                ->exists();
    }
}

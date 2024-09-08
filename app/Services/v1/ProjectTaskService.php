<?php

namespace App\Services\v1;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectTaskService
{
    public function getTasks(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        $query = ProjectTask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    });

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('project_task_id', 'LIKE', "%$search%")
                    ->orWhere('project_task_name', 'LIKE', "%$search%")
                    ->orWhere('project_task_description', 'LIKE', "%$search%")
                    ->orWhere('project_id', 'LIKE', "%$search%")
                    ->orWhere('project_task_progress', 'LIKE', "%$search%")
                    ->orWhere('project_task_priority_level', 'LIKE', "%$search%")
                    ->orWhere('user_id', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);
            
        // Construct the response data
        $responseData = [
            'message' => $tasks->isEmpty() ? 'No tasks found for the provided criteria.' : 'Tasks retrieved successfully.',
            'data' => $tasks,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getTaskById(Authenticatable $user, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the Task for the given ID and check if it exists
        $task = ProjectTask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
                    ->where('project_id', $projectId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle Task not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Task retrieved successfully.',
            'data' => $task,
        ], 200);
    }

    public function createTask(Authenticatable $user, array $validatedData, int $projectId): JsonResponse
    {
        // Check if the user is part of the project
        $project = Project::select('project_id')
                        ->where('project_id', $projectId)
                        ->whereHas('users', function ($query) use ($user) {
                            $query->where('users.company_id', $user->company_id)
                                ->where('users.user_id', $user->user_id);
                        })
                        ->first();
        
        // Handle case where user is not part of the project
        if (!$project) {
            return response()->json(['message' => 'You are not part of the project.'], 409);
        }

        // Create a new task in the project
        ProjectTask::create([
            'project_task_name' => $validatedData['project_task_name'],
            'project_task_description' => $validatedData['project_task_description'] ?? null,
            'project_id' => $project->project_id,
            'project_task_progress' => $validatedData['project_task_progress'],
            'project_task_priority_level' => $validatedData['project_task_priority_level'],
        ]);

        // Return the response as JSON with a 201 status code
        return response()->json(['message' => 'Task created successfully.'], 201);
    }

    public function updateTask(Authenticatable $user, array $validatedData, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();
    
        // Handle case where task is not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }
    
        // Update the task attributes with validated data
        $task->fill($validatedData);
    
        // Check if any fields have changed using isDirty()
        if (!$task->isDirty()) {
            return response()->json(['message' => 'No changes detected.'], 400);
        }
    
        // Proceed with saving changes if there are updates
        $task->save();
    
        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Task updated successfully.'], 200);
    }

    public function deleteTask(Authenticatable $user, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where task is not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        // Delete the task
        $task->delete();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Task deleted successfully.'], 200);
    }

    public function assignUserToTask(Authenticatable $user, array $validatedData, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where task is not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        // Retrieve the user and check if it exists in the project
        $projectUserExists = ProjectUser::where('user_id', $validatedData['user_id'])
                                ->where('company_id', $user->company_id)
                                ->where('project_id', $projectId)
                                ->exists();
        
        // Handle case where user does not exist in the project
        if (!$projectUserExists) {
            return response()->json(['message' => 'User not found in the project.'], 404);
        }

        // Check if a user is already assigned to the task
        if ($task->user_id) {
            return response()->json(['message' => 'A user has already been assigned.'], 409);
        }

        // Save the user
        $task->user_id = $validatedData['user_id'];
        $task->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User assigned to task successfully.'], 200);
    }

    public function removeUserFromTask(Authenticatable $user, array $validatedData, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) use ($user) {
                        $query->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where task is not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        // Retrieve the user and check if it exists in the project
        $projectUserExists = ProjectUser::where('user_id', $validatedData['user_id'])
                                ->where('company_id', $user->company_id)
                                ->where('project_id', $projectId)
                                ->exists();

        // Handle case where user does not exist in the project
        if (!$projectUserExists) {
            return response()->json(['message' => 'User not found in the project.'], 404);
        }

        // Check if a user has not assigned to the task
        if (!$task->user_id || (int) $task->user_id !== (int) $validatedData['user_id']) {
            return response()->json(['message' => 'User has not been assigned.'], 409);
        }

        // Remove the user
        $task->user_id = null;
        $task->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User removed from task successfully.'], 200);
    }
}
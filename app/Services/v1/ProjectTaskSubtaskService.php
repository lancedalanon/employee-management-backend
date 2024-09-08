<?php

namespace App\Services\v1;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectTaskSubtaskService
{
    public function getSubtasks(Authenticatable $user, array $validatedData, int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        $query = ProjectTaskSubtask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    });

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('project_task_subtask_id', 'LIKE', "%$search%")
                    ->orWhere('project_task_subtask_name', 'LIKE', "%$search%")
                    ->orWhere('project_task_subtask_description', 'LIKE', "%$search%")
                    ->orWhere('project_task_id', 'LIKE', "%$search%")
                    ->orWhere('project_task_subtask_progress', 'LIKE', "%$search%")
                    ->orWhere('project_task_subtask_priority_level', 'LIKE', "%$search%")
                    ->orWhere('user_id', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);
            
        // Construct the response data
        $responseData = [
            'message' => $tasks->isEmpty() ? 'No subtasks found for the provided criteria.' : 'Subtasks retrieved successfully.',
            'data' => $tasks,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getSubtaskById(Authenticatable $user, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Retrieve the Subtask for the given ID and check if it exists
        $subtask = ProjectTaskSubtask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
            ->where('project_task_id', $taskId)
            ->where('project_task_subtask_id', $subtaskId)
            ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                $query->where('project_id', $projectId)
                    ->where('users.company_id', $user->company_id)
                    ->where('users.user_id', $user->user_id);
            })
            ->first();

        // Handle Subtask not found
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Subtask retrieved successfully.',
            'data' => $subtask,
        ], 200);
    }

    public function createSubtask(Authenticatable $user, array $validatedData, int $projectId, int $taskId): JsonResponse
    {
       // Check if the user is part of the project
       $projectTask = ProjectTask::select('project_task_id')
                        ->where('project_id', $projectId)
                        ->where('project_task_id', $taskId)
                        ->whereHas('project.users', function ($query) use ($user) {
                            $query->where('users.company_id', $user->company_id)
                                ->where('users.user_id', $user->user_id);
                        })
                        ->first();

        // Handle case where user is not part of the project
        if (!$projectTask) {
            return response()->json(['message' => 'You are not part of the project.'], 409);
        }

        // Create a new subtask in the project
        ProjectTaskSubtask::create([
            'project_task_subtask_name' => $validatedData['project_task_subtask_name'],
            'project_task_subtask_description' => $validatedData['project_task_subtask_description'] ?? null,
            'project_task_id' => $projectTask->project_task_id,
            'project_task_subtask_progress' => $validatedData['project_task_subtask_progress'],
            'project_task_subtask_priority_level' => $validatedData['project_task_subtask_priority_level'],
        ]);

        // Return the response as JSON with a 201 status code
        return response()->json(['message' => 'Subtask created successfully.'], 201);
    }

    public function updateSubtask(Authenticatable $user, array $validatedData, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Retrieve the subtask and check if the user is part of the project
        $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where subtask is not found
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found.'], 404);
        }

        // Update the subtask attributes with validated data
        $subtask->fill($validatedData);

        // Check if any fields have changed using isDirty()
        if (!$subtask->isDirty()) {
            return response()->json(['message' => 'No changes detected.'], 400);
        }

        // Proceed with saving changes if there are updates
        $subtask->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Subtask updated successfully.'], 200);
    }

    public function deleteSubtask(Authenticatable $user, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
       // Retrieve the subtask and check if the user is part of the project
       $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where subtask is not found
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found.'], 404);
        }

        // Delete the subtask
        $subtask->delete();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Subtask deleted successfully.'], 200);
    }

    public function assignUserToSubtask(Authenticatable $user, array $validatedData, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Retrieve the subtask and check if the user is part of the project
        $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where subtask is not found
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found.'], 404);
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
        if ($subtask->user_id) {
            return response()->json(['message' => 'A user has already been assigned.'], 409);
        }

        // Save the user
        $subtask->user_id = $validatedData['user_id'];
        $subtask->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User assigned to subtask successfully.'], 200);
    }

    public function removeUserFromSubtask(Authenticatable $user, array $validatedData, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Retrieve the subtask and check if the user is part of the project
        $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task.project.users', function ($query) use ($projectId, $user) {
                        $query->where('project_id', $projectId)
                            ->where('users.company_id', $user->company_id)
                            ->where('users.user_id', $user->user_id);
                    })
                    ->first();

        // Handle case where subtask is not found
        if (!$subtask) {
            return response()->json(['message' => 'Subtask not found.'], 404);
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
        if (!$subtask->user_id || (int) $subtask->user_id !== (int) $validatedData['user_id']) {
            return response()->json(['message' => 'User has not been assigned.'], 409);
        }

        // Remove the user
        $subtask->user_id = null;
        $subtask->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'User removed from subtask successfully.'], 200);
    }
}
<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectTaskController\IndexRequest;
use App\Http\Requests\v1\ProjectTaskController\StoreRequest;
use App\Http\Requests\v1\ProjectTaskController\UpdateRequest;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    protected Authenticatable $user;

    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }

    public function index(IndexRequest $request, int $projectId): JsonResponse
    {
        $validatedData = $request->validated();

        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        $query = ProjectTask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) {
                        $query->where('users.user_id', $this->user->user_id);
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

    public function show(int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the Task for the given ID and check if it exists
        $task = ProjectTask::with('user:user_id,first_name,middle_name,last_name,suffix,username')
                    ->where('project_id', $projectId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('project.users', function ($query) {
                        $query->where('users.user_id', $this->user->user_id);
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

    public function store(StoreRequest $request, int $projectId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Check if the user is part of the project
        $project = Project::select('project_id')
                        ->where('project_id', $projectId)
                        ->whereHas('users', function ($query) {
                            $query->where('users.user_id', $this->user->user_id);
                        })
                        ->first();
        
        // Handle case where user is not part of the project
        if (!$project) {
            return response()->json(['message' => 'You are not part of the project.'], 403);
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

    public function update(UpdateRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();
    
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) {
                        $query->where('users.user_id', $this->user->user_id);
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

    public function destroy(int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the task and check if the user is part of the project
        $task = ProjectTask::where('project_task_id', $taskId)
                    ->where('project_id', $projectId)
                    ->whereHas('project.users', function ($query) {
                        $query->where('users.user_id', $this->user->user_id);
                    })
                    ->first();

        // Handle case where task is not found
        if (!$task) {
            return response()->json(['message' => 'Task not found.'], 404);
        }

        // Delete the task
        $task->delete();

        // Return the response as JSON with a 204 status code
        return response()->json(['message' => 'Task deleted successfully.'], 200);
    }
}

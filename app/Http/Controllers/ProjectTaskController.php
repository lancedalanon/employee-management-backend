<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectTaskController extends Controller
{
    /**
     * Retrieve tasks for the given project ID.
     *
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTasks($projectId)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Retrieve tasks for the given project ID, with pagination
            $tasks = ProjectTask::where('project_id', $project->project_id)->paginate(10);

            // Handle case where tasks are not found
            if ($tasks->isEmpty()) {
                return response()->json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Return the ProjectTask entry as a JSON response
            return response()->json([
                'message' => 'Task entry retrieved successfully.',
                'data' => $tasks->items(),
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to retrieve task entry.',
            ], 500);
        }
    }

    /**
     * Retrieve a specific task by its ID and project ID.
     *
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskById($projectId, $id)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Retrieve the specific task for the given project ID and task ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $id)->first();

            // Handle case where task is not found
            if (!$task) {
                return response()->json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Return the specific ProjectTask entry as a JSON response
            return response()->json([
                'message' => 'Task entry retrieved successfully.',
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to retrieve task entry.',
            ], 500);
        }
    }

    /**
     * Create a new task for the given project ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask(Request $request, $projectId)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'project_task_name' => 'required|string|max:255',
            'project_task_description' => 'required|string',
            'project_task_progress' => 'required|string|in:Not started,In progress,Reviewing,Completed',
            'project_task_priority_level' => 'required|string|in:Low,Medium,High',
        ]);

        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Create a new task for the given project ID
            $task = new ProjectTask([
                'project_task_name' => $validated['project_task_name'],
                'project_task_description' => $validated['project_task_description'],
                'project_task_progress' => $validated['project_task_progress'],
                'project_task_priority_level' => $validated['project_task_priority_level'],
                'project_id' => $project->project_id,
            ]);

            // Save the task to the database
            $task->save();

            // Return the newly created ProjectTask entry as a JSON response
            return response()->json([
                'message' => 'Task created successfully.',
                'data' => $task
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to create task.',
            ], 500);
        }
    }

    /**
     * Update an existing task for the given project ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask(Request $request, $projectId, $id)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'project_task_name' => 'required|string|max:255',
            'project_task_description' => 'required|string',
            'project_task_progress' => 'required|string|in:Not started,In progress,Reviewing,Completed',
            'project_task_priority_level' => 'required|string|in:Low,Medium,High',
        ]);

        try {
            // Check if the project exists
            $project = Project::find($projectId);

            // Handle case where project is not found
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Find the task by its ID and project ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $id)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return response()->json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Update the task with validated data
            $task->project_task_name = $validated['project_task_name'];
            $task->project_task_description = $validated['project_task_description'];
            $task->project_task_progress = $validated['project_task_progress'];
            $task->project_task_priority_level = $validated['project_task_priority_level'];

            // Save the updated task to the database
            $task->save();

            // Return the updated ProjectTask entry as a JSON response
            return response()->json([
                'message' => 'Task updated successfully.',
                'data' => $task
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to update task.',
            ], 500);
        }
    }

    /**
     * Soft delete an existing task for the given project ID.
     *
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTask($projectId, $id)
    {
        try {
            // Check if the project exists
            $project = Project::where('project_id', $projectId)->first();

            // Handle case where project is not found
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Find the task by its ID and project ID
            $task = ProjectTask::where('project_id', $project->project_id)
                ->where('project_task_id', $id)
                ->first();

            // Handle case where task is not found
            if ($task->isEmpty()) {
                return response()->json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Soft delete the task
            $task->delete();

            // Return a JSON response indicating success
            return response()->json([
                'message' => 'Task deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to delete task.',
            ], 500);
        }
    }
}

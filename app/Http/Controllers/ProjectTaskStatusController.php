<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectTaskStatusController extends Controller
{
    /**
     * Get all statuses for a specific task within a project with pagination.
     *
     * @param int $projectId
     * @param int $taskId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses(int $projectId, int $taskId, Request $request)
    {
        try {

            // Get the number of items per page from the query parameters, default to 10
            $perPage = $request->query('perPage', 10);

            // Fetch paginated statuses for the given task within the project
            $statuses = ProjectTaskStatus::where('project_task_id', $taskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->paginate($perPage);

            // Return the specific ProjectTaskStatus entry as a JSON response
            return response()->json([
                'message' => 'Statuses entry retrieved successfully.',
                'data' => $statuses
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to retrieve statuses entry.',
            ], 500);
        }
    }

    /**
     * Get a specific status by its ID within a task of a project.
     *
     * @param int $projectId
     * @param int $taskId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusById(int $projectId, int $taskId, int $id)
    {
        try {
            // Find the task by its ID and project ID
            $task = ProjectTask::where('project_id', $projectId)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return response()->json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Fetch the status by its ID
            $status = ProjectTaskStatus::where('project_task_id', $task->project_id)
                ->where('project_task_status_id', $id)
                ->first();

            // Return the specific ProjectTaskStatus entry as a JSON response
            return response()->json([
                'message' => 'Status entry retrieved successfully.',
                'data' => $status
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to retrieve status entry.',
            ], 500);
        }
    }

    /**
     * Create a new status for a specific task within a project.
     *
     * @param int $projectId
     * @param int $taskId
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createStatus(int $projectId, int $taskId, Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'project_task_status' => 'required|string|max:255',
            'project_task_status_media_file' => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov,wmv|max:20480',
        ]);

        // Find the task that matches the projectId and taskId
        $task = ProjectTask::where('project_id', $projectId)
            ->where('project_task_id', $taskId)
            ->first();

        if (!$task) {
            // Return a 404 response if the task is not found
            return response()->json([
                'message' => 'Task not found.',
            ], 404);
        }

        try {
            // Handle the file upload if a file is provided
            $filePath = null;
            if ($request->hasFile('project_task_status_media_file')) {
                $file = $request->file('project_task_status_media_file');
                $filePath = $file->store('project_task_status_media_file', 'public');
            }

            // Create a new status
            $status = ProjectTaskStatus::create([
                'project_task_id' => $taskId,
                'project_task_status' => $validatedData['project_task_status'],
                'project_task_status_media_file' => $filePath,
            ]);

            // Return the specific ProjectTaskStatus entry as a JSON response
            return response()->json([
                'message' => 'Status entry created successfully.',
                'data' => $status,
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to create status entry.',
            ], 500);
        }
    }

    /**
     * Update a specific status for a task within a project.
     *
     * @param int $projectId
     * @param int $taskId
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(int $projectId, int $taskId, int $id, Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'project_task_status' => 'required|string|max:255',
            'project_task_status_media_file' => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov,wmv|max:20480',
        ]);

        // Find the task that matches the projectId and taskId
        $task = ProjectTask::where('project_id', $projectId)
            ->where('project_task_id', $taskId)
            ->first();

        if (!$task) {
            // Return a 404 response if the task is not found
            return response()->json([
                'message' => 'Task not found.',
            ], 404);
        }

        // Find the status by its ID
        $status = ProjectTaskStatus::where('project_task_id', $task->project_task_id)
            ->where('project_task_status_id', $id)
            ->first();

        if (!$status) {
            // Return a 404 response if the status is not found
            return response()->json([
                'message' => 'Status not found.',
            ], 404);
        }

        try {
            // Handle the file upload if a file is provided
            $filePath = $status->project_task_status_media_file;
            if ($request->hasFile('project_task_status_media_file')) {
                $file = $request->file('project_task_status_media_file');
                $filePath = $file->store('project_task_status_media_file', 'public');
            }

            // Update the status
            $status->update([
                'project_task_status' => $validatedData['project_task_status'],
                'project_task_status_media_file' => $filePath,
            ]);

            // Return the updated ProjectTaskStatus entry as a JSON response
            return response()->json([
                'message' => 'Status entry updated successfully.',
                'data' => $status,
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to update status entry.',
            ], 500);
        }
    }


    /**
     * Delete a specific status for a task within a project.
     *
     * @param int $projectId
     * @param int $taskId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteStatus(int $projectId, int $taskId, int $id)
    {
        // Find the task that matches the projectId and taskId
        $task = ProjectTask::where('project_id', $projectId)
            ->where('project_task_id', $taskId)
            ->first();

        if (!$task) {
            // Return a 404 response if the task is not found
            return response()->json([
                'message' => 'Task not found.',
            ], 404);
        }

        // Find the status by its ID
        $status = ProjectTaskStatus::where('project_task_id', $task->project_task_id)
            ->where('project_task_status_id', $id)
            ->first();

        if (!$status) {
            // Return a 404 response if the status is not found
            return response()->json([
                'message' => 'Status not found.',
            ], 404);
        }

        try {
            // Perform a soft delete
            $status->delete();

            // Return a success message
            return response()->json([
                'message' => 'Status entry deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return response()->json([
                'message' => 'Failed to delete status entry.',
            ], 500);
        }
    }
}

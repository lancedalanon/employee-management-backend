<?php

namespace App\Services;

use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\CacheService;

class ProjectTaskStatusService
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(int $projectId, int $taskId, int $perPage, int $page)
    {
        try {
            $cacheKey = "project_task_statuses_perPage_{$perPage}_page_{$page}";

            // Fetch paginated statuses for the given task within the project
            $statuses = $this->cacheService->rememberForever($cacheKey, function () use ($perPage, $page, $projectId, $taskId) {
                return ProjectTaskStatus::where('project_task_id', $taskId)
                    ->whereHas('task', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->paginate($perPage, ['*'], 'page', $page);
            });

            // Return the specific ProjectTaskStatus as a JSON response
            return Response::json([
                'message' => 'Statuses retrieved successfully.',
                'current_page' => $statuses->currentPage(),
                'data' => $statuses->items(),
                'first_page_url' => $statuses->url(1),
                'from' => $statuses->firstItem(),
                'last_page' => $statuses->lastPage(),
                'last_page_url' => $statuses->url($statuses->lastPage()),
                'links' => $statuses->linkCollection()->toArray(),
                'next_page_url' => $statuses->nextPageUrl(),
                'path' => $statuses->path(),
                'per_page' => $statuses->perPage(),
                'prev_page_url' => $statuses->previousPageUrl(),
                'to' => $statuses->lastItem(),
                'total' => $statuses->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve statuses.',
            ], 500);
        }
    }

    public function show(int $projectId, int $taskId, int $statusId)
    {
        try {
            // Create cache key for the specific status entry
            $cacheKey = "project_task_status_projectId{$projectId}_taskId{$taskId}_statusId{$statusId}";

            // Fetch the status by its ID
            $status = $this->cacheService->rememberForever($cacheKey, function () use ($projectId, $taskId, $statusId) {
                return ProjectTaskStatus::where('project_task_status_id', $statusId)
                    ->where('project_task_id', $taskId)
                    ->whereHas('task', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    })
                    ->first();
            });

            // Handle case where status is not found
            if (!$status) {
                return Response::json([
                    'message' => 'Status not found.',
                ], 404);
            }

            // Return the specific ProjectTaskStatus as a JSON response
            return Response::json([
                'message' => 'Status retrieved successfully.',
                'data' => $status
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve status',
            ], 500);
        }
    }

    public function store(array $validatedData, int $projectId, int $taskId, ?UploadedFile $mediaFile)
    {
        try {
            // Fetch the task by its ID
            $task = ProjectTask::where('project_id', $projectId)
                ->where('project_task_id', $taskId)
                ->first();

            // Handle case where task is not found
            if (!$task) {
                return Response::json([
                    'message' => 'Task not found.',
                ], 404);
            }

            // Create a new ProjectTaskStatus entry
            $status = new ProjectTaskStatus();
            $status->project_task_status = $validatedData['project_task_status'];
            $status->project_task_id = $taskId;

            // If there's a media file, handle the file upload
            if ($mediaFile instanceof UploadedFile) {
                // Validate and handle the file upload
                $filePath = $mediaFile->store('project_task_status_media_files', 'public');
                $status->project_task_status_media_file = $filePath;
            }

            // Save the status to the database
            $status->save();

            // Return a JSON response with the created status
            return Response::json([
                'message' => 'Status created successfully.',
                'data' => $status,
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to create status.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId, int $taskId, int $statusId, ?UploadedFile $mediaFile)
    {
        try {
            // Fetch the status by its ID
            $status = ProjectTaskStatus::where('project_task_status_id', $statusId)
                ->where('project_task_id', $taskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            // Handle case where status is not found
            if (!$status) {
                return Response::json([
                    'message' => 'Status not found.',
                ], 404);
            }

            // Update ProjectTaskStatus entry
            $status->project_task_status = $validatedData['project_task_status'];
            $status->project_task_id = $taskId;

            // If there's a media file, handle the file upload
            if ($mediaFile instanceof UploadedFile) {
                // Delete the previous file if it exists
                if ($status->project_task_status_media_file) {
                    Storage::disk('public')->delete($status->project_task_status_media_file);
                }

                // Validate and handle the file upload
                $filePath = $mediaFile->store('project_task_status_media_files', 'public');
                $status->project_task_status_media_file = $filePath;
            }

            // Save the status entry to the database
            $status->save();

            // Return success response with updated DTR data
            return Response::json([
                'message' => 'Status updated successfully.',
                'data' => $status,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'Failed to update status.',
            ], 500);
        }
    }

    public function destroy(int $projectId, int $taskId, int $statusId)
    {
        try {
            // Fetch the status by its ID
            $status = ProjectTaskStatus::where('project_task_status_id', $statusId)
                ->where('project_task_id', $taskId)
                ->whereHas('task', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                ->first();

            // Handle case where status is not found
            if (!$status) {
                return Response::json([
                    'message' => 'Status not found.',
                ], 404);
            }

            // Perform a soft delete
            $status->delete();

            // Return a success message
            return Response::json([
                'message' => 'Status deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to delete status.',
            ], 500);
        }
    }
}

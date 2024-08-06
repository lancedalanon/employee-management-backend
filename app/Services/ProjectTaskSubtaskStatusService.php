<?php

namespace App\Services;

use App\Models\ProjectTaskStatus;
use App\Models\ProjectTaskSubtask;
use App\Models\ProjectTaskSubtaskStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ProjectTaskSubtaskStatusService
{
    protected $cacheService;
    protected $userId;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->userId = Auth::id();
    }

    public function index(int $perPage, int $page, int $projectId, int $taskId, int $subtaskId) 
    {
        try {
            // Check if the user has access to the subtask
            if (!$this->isUserAuthorized($projectId, $taskId, $subtaskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            $subtaskStatus = ProjectTaskSubtaskStatus::where('project_task_subtask_id', $subtaskId)
                ->whereHas('subtask', function ($query) use ($taskId, $projectId) {
                    $query->where('project_task_id', $taskId)       
                    ->whereHas('task', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId)
                        ->whereHas('project.users', function ($query){
                            $query->where('users.user_id', $this->userId);
                        });
                    });
                })
                ->paginate($perPage, ['*'], 'page', $page);

            // Return the specific ProjectTaskSubtaskStatus as a JSON response
            return Response::json([
                'message' => 'Subtask statuses retrieved successfully.',
                'current_page' => $subtaskStatus->currentPage(),
                'data' => $subtaskStatus->items(),
                'first_page_url' => $subtaskStatus->url(1),
                'from' => $subtaskStatus->firstItem(),
                'last_page' => $subtaskStatus->lastPage(),
                'last_page_url' => $subtaskStatus->url($subtaskStatus->lastPage()),
                'links' => $subtaskStatus->linkCollection()->toArray(),
                'next_page_url' => $subtaskStatus->nextPageUrl(),
                'path' => $subtaskStatus->path(),
                'per_page' => $subtaskStatus->perPage(),
                'prev_page_url' => $subtaskStatus->previousPageUrl(),
                'to' => $subtaskStatus->lastItem(),
                'total' => $subtaskStatus->total(),
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve subtask statuses.',
            ], 500);
        }
    }

    public function show(int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId) 
    {
        try {
            // Check if the user has access to the status
            if (!$this->isUserAuthorized($projectId, $taskId, $subtaskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            // Fetch the status by its ID
            $subtaskStatus = ProjectTaskSubtaskStatus::where('project_task_subtask_status_id', $subtaskStatusId)
                ->where('project_task_subtask_id', $subtaskId)
                ->whereHas('subtask', function ($query) use ($taskId, $projectId) {
                    $query->where('project_task_id', $taskId)       
                    ->whereHas('task', function ($query) use ($projectId) {
                        $query->where('project_id', $projectId)
                        ->whereHas('project.users', function ($query){
                            $query->where('users.user_id', $this->userId);
                        });
                    });
                })
                ->first();

            // Handle case where status is not found
            if (!$subtaskStatus) {
                return Response::json([
                    'message' => 'Subtask status not found.',
                ], 404);
            }

            // Return the specific ProjectTaskStatus as a JSON response
            return Response::json([
                'message' => 'Subtask status retrieved successfully.',
                'data' => $subtaskStatus
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to retrieve status',
            ], 500);
        }
    }

    public function store(array $validatedData, int $projectId, int $taskId, int $subtaskId, ?UploadedFile $mediaFile) 
    {
        try {
            // Check if the user has access to the status
            if (!$this->isUserAuthorized($projectId, $taskId, $subtaskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            // Fetch the task by its ID
            $subtask = ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
                ->whereHas('task', function ($query) use ($projectId, $taskId) {
                    $query->where('project_id', $projectId)
                    ->where('project_task_id', $taskId);
                })
                ->first();

            // Handle case where subtask is not found
            if (!$subtask) {
                return Response::json([
                    'message' => 'Subtask not found.',
                ], 404);
            }

            // Create a new ProjectTaskStatus entry
            $subtaskStatus = new ProjectTaskSubtaskStatus();
            $subtaskStatus->project_task_subtask_status = $validatedData['project_task_subtask_status'];
            $subtaskStatus->project_task_subtask_id = $subtaskId;

            // If there's a media file, handle the file upload
            if ($mediaFile instanceof UploadedFile) {
                // Validate and handle the file upload
                $filePath = $mediaFile->store('project_task_subtask_status_media_files', 'public');
                $subtaskStatus->project_task_subtask_status_media_file = $filePath;
            }

            // Save the status to the database
            $subtaskStatus->save();

            // Return a JSON response with the created status
            return Response::json([
                'message' => 'Subtask status created successfully.',
                'data' => $subtaskStatus,
            ], 201);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to create subtask status.',
            ], 500);
        }
    }

    public function update(array $validatedData, int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId, ?UploadedFile $mediaFile) 
    {
        try {
            if (!$this->isUserAuthorized($projectId, $taskId, $subtaskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            $subtaskStatus = $this->isSubtaskStatusExisting($projectId, $taskId, $subtaskId, $subtaskStatusId);

            if (!$subtaskStatus) {
                return Response::json([
                    'message' => 'Subtask status not found.',
                ], 404);
            }

            $subtaskStatus->project_task_subtask_status = $validatedData['project_task_subtask_status'];

            // If there's a media file, handle the file upload
            if ($mediaFile instanceof UploadedFile) {
                // Delete the previous file if it exists
                if ($subtaskStatus->project_task_subtask_status_media_file) {
                    Storage::disk('public')->delete($subtaskStatus->project_task_subtask_status_media_file);
                }

                // Validate and handle the file upload
                $filePath = $mediaFile->store('project_task_subtask_status_media_files', 'public');
                $subtaskStatus->project_task_subtask_status_media_file = $filePath;
            }

            // Save the status entry to the database
            $subtaskStatus->save();

            return Response::json([
                'message' => 'Subtask status updated successfully.',
                'data' => $subtaskStatus,
            ], 200);
        } catch (\Exception $e) {
            // Handle any other errors that occur during the process
            return Response::json([
                'message' => 'Failed to update subtask status.',
            ], 500);
        }
    }

    public function destroy(int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId) 
    {
        try {
            // Check if the user has access to the subtask status
            if (!$this->isUserAuthorized($projectId, $taskId, $subtaskId)) {
                return Response::json([
                    'message' => 'Forbidden.',
                ], 403);
            }

            $subtaskStatus = $this->isSubtaskStatusExisting($projectId, $taskId, $subtaskId, $subtaskStatusId);

            if (!$subtaskStatus) {
                return Response::json([
                    'message' => 'Subtask status not found.',
                ], 404);
            }

            // Perform a soft delete
            $subtaskStatus->delete();

            // Return a success message
            return Response::json([
                'message' => 'Subtask status deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            // Return a JSON response indicating the error
            return Response::json([
                'message' => 'Failed to delete subtask status.',
            ], 500);
        }
    }

    protected function isSubtaskStatusExisting(int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId)
    {
        return ProjectTaskSubtaskStatus::where('project_task_subtask_status_id', $subtaskStatusId)
        ->where('project_task_subtask_id', $subtaskId)
        ->whereHas('subtask', function ($query) use ($taskId, $projectId) {
            $query->where('project_task_id', $taskId)       
            ->whereHas('task', function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                ->whereHas('project.users', function ($query){
                    $query->where('users.user_id', $this->userId);
                });
            });
        })
        ->first();
    }

    protected function isUserAuthorized(int $projectId, int $taskId, int $subtaskId)
    {
        return ProjectTaskSubtask::where('project_task_subtask_id', $subtaskId)
        ->where('project_task_id', $taskId)
        ->whereHas('task', function ($query) use ($projectId) {
            $query->where('project_id', $projectId)
            ->whereHas('project.users', function ($query){
                $query->where('users.user_id', $this->userId);
            });
        })
        ->exists();
    }
}
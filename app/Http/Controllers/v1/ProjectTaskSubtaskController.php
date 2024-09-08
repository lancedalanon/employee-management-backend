<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectTaskSubtaskController\AssignUserRequest;
use App\Http\Requests\v1\ProjectTaskSubtaskController\IndexRequest;
use App\Http\Requests\v1\ProjectTaskSubtaskController\RemoveUserRequest;
use App\Http\Requests\v1\ProjectTaskSubtaskController\StoreRequest;
use App\Http\Requests\v1\ProjectTaskSubtaskController\UpdateRequest;
use App\Services\v1\ProjectTaskSubtaskService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectTaskSubtaskController extends Controller
{
    protected Authenticatable $user;
    protected ProjectTaskSubtaskService $projectTaskSubtaskService;

    public function __construct(Authenticatable $user, ProjectTaskSubtaskService $projectTaskSubtaskService)
    {
        $this->user = $user;
        $this->projectTaskSubtaskService = $projectTaskSubtaskService;
    }

    public function index(IndexRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();
        
        // Retrieve the query parameters from the request
        return $this->projectTaskSubtaskService->getSubtasks($this->user, $validatedData, $projectId, $taskId);
    }

    public function show(int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Retrieve the query parameters from the request
        return $this->projectTaskSubtaskService->getSubtaskById($this->user, $projectId, $taskId, $subtaskId);
    }

    public function store(StoreRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Create the project task subtask
        return $this->projectTaskSubtaskService->createSubtask($this->user, $validatedData, $projectId, $taskId);
    }

    public function update(UpdateRequest $request, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Update the project task subtask
        return $this->projectTaskSubtaskService->updateSubtask($this->user, $validatedData, $projectId, $taskId, $subtaskId);
    }

    public function destroy(int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Delete the project task subtask
        return $this->projectTaskSubtaskService->deleteSubtask($this->user, $projectId, $taskId, $subtaskId);
    }

    public function assignUser(AssignUserRequest $request, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Assign the user to the subtask
        return $this->projectTaskSubtaskService->assignUserToSubtask($this->user, $validatedData, $projectId, $taskId, $subtaskId);
    }

    public function removeUser(RemoveUserRequest $request, int $projectId, int $taskId, int $subtaskId): JsonResponse
    {
        // Get the validated data
        $validatedData = $request->validated();

        // Remove the user to the subtask
        return $this->projectTaskSubtaskService->removeUserFromSubtask($this->user, $validatedData, $projectId, $taskId, $subtaskId);
    }
}

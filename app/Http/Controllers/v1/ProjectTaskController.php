<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectTaskController\AssignUserRequest;
use App\Http\Requests\v1\ProjectTaskController\IndexRequest;
use App\Http\Requests\v1\ProjectTaskController\RemoveUserRequest;
use App\Http\Requests\v1\ProjectTaskController\StoreRequest;
use App\Http\Requests\v1\ProjectTaskController\UpdateRequest;
use App\Services\v1\ProjectTaskService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectTaskController extends Controller
{
    protected Authenticatable $user;
    protected ProjectTaskService $projectTaskService;

    public function __construct(Authenticatable $user, ProjectTaskService $projectTaskService)
    {
        $this->user = $user;
        $this->projectTaskService = $projectTaskService;
    }

    public function index(IndexRequest $request, int $projectId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();
        
        // Retrieve the query parameters from the request
        return $this->projectTaskService->getTasks($this->user, $validatedData, $projectId);
    }

    public function show(int $projectId, int $taskId): JsonResponse
    {
        // Retrieve the query parameters from the request
        return $this->projectTaskService->getTaskById($this->user, $projectId, $taskId);
    }

    public function store(StoreRequest $request, int $projectId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Create the project task
        return $this->projectTaskService->createTask($this->user, $validatedData, $projectId);
    }

    public function update(UpdateRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Update the project task
        return $this->projectTaskService->updateTask($this->user, $validatedData, $projectId, $taskId);
    }    

    public function destroy(int $projectId, int $taskId): JsonResponse
    {
        // Delete the project task
        return $this->projectTaskService->deleteTask($this->user, $projectId, $taskId);
    }

    public function assignUser(AssignUserRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Add a user to the project task
        return $this->projectTaskService->assignUserToTask($this->user, $validatedData, $projectId, $taskId);
    }

    public function removeUser(RemoveUserRequest $request, int $projectId, int $taskId): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Remove a user from the project task
        return $this->projectTaskService->removeUserFromTask($this->user, $validatedData, $projectId, $taskId);
    }
}

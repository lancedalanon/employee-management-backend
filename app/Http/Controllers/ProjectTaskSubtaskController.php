<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectTaskSubtask\StoreSubtaskRequest;
use App\Http\Requests\ProjectTaskSubtask\UpdateSubtaskRequest;
use Illuminate\Http\Request;
use App\Services\ProjectTaskSubtaskService;

class ProjectTaskSubtaskController extends Controller
{
    protected $projectTaskSubtaskService;

    public function __construct(ProjectTaskSubtaskService $projectTaskSubtaskService)
    {
        $this->projectTaskSubtaskService = $projectTaskSubtaskService;
    }

    public function index(Request $request, int $projectId, int $taskId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectTaskSubtaskService->index($perPage, $page, $projectId, $taskId);
        return $response;
    }

    public function show(int $projectId, int $taskId, int $subtaskId)
    {
        $response = $this->projectTaskSubtaskService->show($projectId, $taskId, $subtaskId);
        return $response;
    }

    public function store(StoreSubtaskRequest $request, int $projectId, int $taskId)
    {
        $validatedData = $request->validated();
        $response = $this->projectTaskSubtaskService->store($validatedData, $projectId, $taskId);
        return $response;
    }

    public function update(UpdateSubtaskRequest $request, int $projectId, int $taskId, int $subtaskId)
    {
        $validatedData = $request->validated();
        $response = $this->projectTaskSubtaskService->update($validatedData, $projectId, $taskId, $subtaskId);
        return $response;
    }

    public function destroy(int $projectId, int $taskId, int $subtaskId)
    {
        $response = $this->projectTaskSubtaskService->destroy($projectId, $taskId, $subtaskId);
        return $response;
    }

    public function addUser(int $projectId, int $taskId, int $subtaskId, int $userId)
    {
        $response = $this->projectTaskSubtaskService->addUser($projectId, $taskId, $subtaskId, $userId);
        return $response;
    }

    public function removeUser(int $projectId, int $taskId, int $subtaskId, int $userId)
    {
        $response = $this->projectTaskSubtaskService->removeUser($projectId, $taskId, $subtaskId, $userId);
        return $response;
    }
}

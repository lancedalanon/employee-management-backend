<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectTask\StoreTaskRequest;
use App\Http\Requests\ProjectTask\UpdateTaskRequest;
use App\Services\ProjectTaskService;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    protected $projectTaskService;

    public function __construct(ProjectTaskService $projectTaskService)
    {
        $this->projectTaskService = $projectTaskService;
    }

    public function index(Request $request, int $projectId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectTaskService->index($perPage, $page, $projectId);
        return $response;
    }

    public function show(int $projectId, int $taskId)
    {
        $response = $this->projectTaskService->show($projectId, $taskId);
        return $response;
    }

    public function store(StoreTaskRequest $request, int $projectId)
    {
        $validatedData = $request->validated();
        $response = $this->projectTaskService->store($validatedData, $projectId);
        return $response;
    }

    public function update(UpdateTaskRequest $request, int $projectId, int $taskId)
    {
        $validatedData = $request->validated();
        $response = $this->projectTaskService->update($validatedData, $projectId, $taskId);
        return $response;
    }

    public function destroy(int $projectId, int $taskId)
    {
        $response = $this->projectTaskService->destroy($projectId, $taskId);
        return $response;
    }
}

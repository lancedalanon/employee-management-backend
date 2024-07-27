<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectTaskStatus\StoreStatusRequest;
use App\Http\Requests\ProjectTaskStatus\UpdateStatusRequest;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Services\ProjectTaskStatusService;
use Illuminate\Http\Request;

class ProjectTaskStatusController extends Controller
{
    protected $projectTaskStatusService;

    public function __construct(ProjectTaskStatusService $projectTaskStatusService)
    {
        $this->projectTaskStatusService = $projectTaskStatusService;
    }

    public function index(Request $request, int $projectId, int $taskId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectTaskStatusService->index($projectId, $taskId, $perPage, $page);
        return $response;
    }

    public function show(int $projectId, int $taskId, int $statusId)
    {
        $response = $this->projectTaskStatusService->show($projectId, $taskId, $statusId);
        return $response;
    }

    public function store(StoreStatusRequest $request, int $taskId, int $projectId)
    {
        $validatedData = $request->validated();
        $mediaFile = $request->file('project_task_status_media_file');
        $response = $this->projectTaskStatusService->store(
            $validatedData,
            $taskId,
            $projectId,
            $mediaFile
        );
        return $response;
    }

    public function update(UpdateStatusRequest $request, int $projectId, int $taskId, int $statusId)
    {
        $validatedData = $request->validated();
        $mediaFile = $request->file('project_task_status_media_file');
        $response = $this->projectTaskStatusService->update(
            $validatedData,
            $projectId,
            $taskId,
            $statusId,
            $mediaFile
        );
        return $response;
    }

    public function destroy(int $projectId, int $taskId, int $statusId)
    {
        $response = $this->projectTaskStatusService->destroy(
            $projectId,
            $taskId,
            $statusId
        );
        return $response;
    }
}

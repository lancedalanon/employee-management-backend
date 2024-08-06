<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectTaskSubtaskStatus\StoreStatusRequest;
use App\Http\Requests\ProjectTaskSubtaskStatus\UpdateStatusRequest;
use App\Services\ProjectTaskSubtaskStatusService;
use Illuminate\Http\Request;

class ProjectTaskSubtaskStatusController extends Controller
{
    protected $projectTaskSubtaskStatusService;

    public function __construct(ProjectTaskSubtaskStatusService $projectTaskSubtaskStatusService)
    {
        $this->projectTaskSubtaskStatusService = $projectTaskSubtaskStatusService;
    }

    public function index(Request $request, int $projectId, int $taskId, int $subtaskId) 
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectTaskSubtaskStatusService->index($perPage, $page, $projectId, $taskId, $subtaskId);
        return $response;
    }

    public function show(int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId) 
    {
        $response = $this->projectTaskSubtaskStatusService->show($projectId, $taskId, $subtaskId, $subtaskStatusId);
        return $response;
    }

    public function store(StoreStatusRequest $request, int $projectId, int $taskId, int $subtaskId) 
    {
        $validatedData = $request->validated();
        $mediaFile = $request->file('project_task_subtask_status_media_file');
        $response = $this->projectTaskSubtaskStatusService->store(
            $validatedData,
            $taskId,
            $projectId,
            $subtaskId,
            $mediaFile
        );
        return $response;
    }

    public function update(UpdateStatusRequest $request, int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId) 
    {
        $validatedData = $request->validated();
        $mediaFile = $request->file('project_task_subtask_status_media_file');
        $response = $this->projectTaskSubtaskStatusService->update(
            $validatedData,
            $taskId,
            $projectId,
            $subtaskId,
            $subtaskStatusId,
            $mediaFile
        );
        return $response;
    }

    public function destroy(int $projectId, int $taskId, int $subtaskId, int $subtaskStatusId) 
    {
        $response = $this->projectTaskSubtaskStatusService->destroy($projectId, $taskId, $subtaskId, $subtaskStatusId);
        return $response;
    }
}

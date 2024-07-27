<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectService->index($perPage, $page);
        return $response;
    }

    public function show(int $projectId)
    {
        $response = $this->projectService->show($projectId);
        return $response;
    }

    public function store(StoreProjectRequest $request)
    {
        $validatedData = $request->validated();
        $response = $this->projectService->store($validatedData);
        return $response;
    }

    public function update(UpdateProjectRequest $request, int $projectId)
    {
        $validatedData = $request->validated();
        $response = $this->projectService->update($validatedData, $projectId);
        return $response;
    }

    public function destroy(int $projectId)
    {
        $response = $this->projectService->destroy($projectId);
        return $response;
    }
}

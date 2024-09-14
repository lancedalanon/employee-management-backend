<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\ProjectController\IndexRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectController\StoreRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectController\UpdateRequest;
use App\Services\v1\CompanyAdmin\ProjectService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    protected Authenticatable $user;
    protected ProjectService $projectService;

    public function __construct(Authenticatable $user, ProjectService $projectService)
    {
        $this->user = $user;
        $this->projectService = $projectService;
    }

    public function index(IndexRequest $request): JsonResponse
    {
        // Get validated data from request
        $validatedData = $request->validated();

        // Retrieve projects based on the given parameters
        return $this->projectService->getProjects($this->user, $validatedData);
    }

    public function show(int $projectId): JsonResponse
    {
        // Retrieve project based on the given parameters
        return $this->projectService->getProjectById($this->user, $projectId);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        // Get validated data from request
        $validatedData = $request->validated();

        // Create project 
        return $this->projectService->createProject($this->user, $validatedData);
    }

    public function update(UpdateRequest $request, int $projectId): JsonResponse
    {
        // Get validated data from request
        $validatedData = $request->validated();

        // Update project 
        return $this->projectService->updateProject($this->user, $projectId, $validatedData);
    }

    public function destroy(int $projectId): JsonResponse
    {
        // Destroy project 
        return $this->projectService->deleteProject($this->user, $projectId);
    }
}

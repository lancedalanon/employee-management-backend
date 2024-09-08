<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectController\IndexRequest;
use App\Models\Project;
use App\Services\v1\ProjectService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        // Validate the request data
        $validatedData = $request->validated();

        // Retrieve the query parameters from the request
        return $this->projectService->getProjects($this->user, $validatedData);
    }

    public function show($projectId): JsonResponse
    {
        // Retrieve the query parameters from the request
        return $this->projectService->getProjectById($this->user, $projectId);
    }
}

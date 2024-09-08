<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\ProjectUserController\IndexRequest;
use App\Services\v1\ProjectUserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class ProjectUserController extends Controller
{
    protected ProjectUserService $projectUserService;
    protected Authenticatable $user;

    public function __construct(Authenticatable $user, ProjectUserService $projectUserService)
    {
        $this->user = $user;
        $this->projectUserService = $projectUserService;
    }

    public function index(IndexRequest $request, int $projectId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData = $request->validated();

        // Retrieve the ProjectUser records based on the given parameters
        return $this->projectUserService->getProjectUsers($this->user, $validatedData, $projectId);
    }

    public function show(int $projectId, int $userId): JsonResponse
    {
        // Retrieve the ProjectUser record based on the given parameters
        return $this->projectUserService->getProjectUsersById($this->user, $projectId, $userId);
    }
}

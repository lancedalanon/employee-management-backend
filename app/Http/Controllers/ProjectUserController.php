<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectUser\DestroyUserRequest;
use App\Http\Requests\ProjectUser\StoreUserRequest;
use App\Http\Requests\ProjectUser\UpdateUserRequest;
use App\Models\ProjectUser;
use App\Services\ProjectUserService;
use Illuminate\Http\Request;

class ProjectUserController extends Controller
{
    protected $projectUserService;

    public function __construct(ProjectUserService $projectUserService)
    {
        $this->projectUserService = $projectUserService;
    }

    public function indexUser(Request $request, $projectId)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->projectUserService->indexUser($projectId, $perPage, $page);
        return $response;
    }

    public function storeUser(StoreUserRequest $request, $projectId)
    {
        $validatedData = $request->validated();
        $response = $this->projectUserService->storeUser($validatedData, $projectId);
        return $response;
    }

    public function destroyUser(DestroyUserRequest $request, $projectId)
    {
        $validatedData = $request->validated();
        $response = $this->projectUserService->destroyUser($validatedData, $projectId);
        return $response;
    }

    public function updateUser(UpdateUserRequest $request, $projectId)
    {
        $validatedData = $request->validated();
        $response = $this->projectUserService->updateUser($validatedData, $projectId);
        return $response;
    }
}

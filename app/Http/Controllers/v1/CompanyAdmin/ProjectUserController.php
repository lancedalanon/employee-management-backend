<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\ProjectController\IndexRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectUserController\BulkAddUsersRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectUserController\BulkRemoveUsersRequest;
use App\Http\Requests\v1\CompanyAdmin\ProjectUserController\ChangeRoleRequest;
use App\Models\ProjectUser;
use App\Models\User;
use App\Services\v1\CompanyAdmin\ProjectUserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectUserController extends Controller
{
    protected ProjectUserService $projectUserService;

    public function __construct(ProjectUserService $projectUserService)
    {
        $this->projectUserService = $projectUserService;
    }

    public function index(Authenticatable $user, IndexRequest $request, int $projectId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData = $request->validated();

        // Retrieve the ProjectUser records based on the given parameters
        return $this->projectUserService->getProjectUsers($user, $validatedData, $projectId);
    }

    public function show(Authenticatable $user, int $projectId, int $userId): JsonResponse
    {
        // Retrieve the ProjectUser record based on the given parameters
        return $this->projectUserService->getProjectUsersById($user, $projectId, $userId);
    }

    public function bulkAddUsers(Authenticatable $user, BulkAddUsersRequest $request, int $projectId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData = $request->validated();

        // Add users into the project in bulk
        return $this->projectUserService->bulkAddUsers($user, $validatedData, $projectId);
    }

    public function bulkRemoveUsers(Authenticatable $user, BulkRemoveUsersRequest $request, int $projectId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData =$request->validated();

        // Remove users into the project in bulk
        return $this->projectUserService->bulkRemoveUsers($user, $validatedData, $projectId);
    }

    public function changeRole(Authenticatable $user, ChangeRoleRequest $request, int $projectId, int $userId): JsonResponse
    {
        // Retrieve validated data from request
        $validatedData = $request->validated();

        // Change user role in project
        return $this->projectUserService->changeRole($user, $validatedData, $projectId, $userId);
    }
}

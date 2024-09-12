<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Admin\UserController\IndexRequest;
use App\Http\Requests\v1\Admin\UserController\StoreRequest;
use App\Http\Requests\v1\Admin\UserController\UpdateRequest;
use App\Services\v1\Admin\UserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;
    protected Authenticatable $user;

    public function __construct(UserService $userService, Authenticatable $user)
    {
        $this->userService =  $userService;
        $this->user = $user;
    }

    public function index(IndexRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Fetch and return users based on the validated data
        return $this->userService->getUsers($validatedData);
    }

    public function show(int $userId): JsonResponse
    {
        // Fetch and return users based on the parameter
        return $this->userService->getUserById($userId);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Create a new user
        return $this->userService->createUser($validatedData);
    }

    public function update(UpdateRequest $request, int $userId): JsonResponse
    {
        // Get validated data
        $validatedData = $request->validated();

        // Update user
        return $this->userService->updateUser($validatedData, $userId);
    }

    public function destroy(int $userId): JsonResponse
    {
        // Delete user
        return $this->userService->deleteUser($userId);
    }
}

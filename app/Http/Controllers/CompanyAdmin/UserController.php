<?php

namespace App\Http\Controllers\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Services\Admin\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $response = $this->userService->index($perPage, $page);

        return $response;
    }

    public function show(int $userId)
    {
        $response = $this->userService->show($userId);

        return $response;
    }

    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();
        $response = $this->userService->store($validatedData);

        return $response;
    }

    public function update(UpdateUserRequest $request, int $userId)
    {
        $validatedData = $request->validated();
        $response = $this->userService->update($validatedData, $userId);

        return $response;
    }

    public function destroy(int $userId)
    {
        $response = $this->userService->destroy($userId);

        return $response;
    }
}

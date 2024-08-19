<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\AuthenticationController\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\v1\AuthenticationService;

class AuthenticationController extends Controller
{
    protected $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Attempt to authenticate the user using the provided credentials
        return $this->authenticationService->login($validatedData);
    }

    public function logout(Request $request): JsonResponse
    {
        // Attempt to logout the authenticated user
        return $this->authenticationService->logout($request);
    }
}

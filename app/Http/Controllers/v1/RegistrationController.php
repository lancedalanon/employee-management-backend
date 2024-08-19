<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\RegistrationController\RegisterCompanyAdminRequest;
use App\Http\Requests\v1\RegistrationController\RegisterRequest;
use App\Http\Requests\v1\RegistrationController\SendInviteRequest;
use App\Services\v1\RegistrationService;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    protected $registrationService;

    public function __construct(RegistrationService $registrationService)
    {
        $this->registrationService = $registrationService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Create a new user
        return $this->registrationService->register($validatedData);
    }
    
    public function registerCompanyAdmin(RegisterCompanyAdminRequest $request): JsonResponse
    {
        // Validate the request data
        $validatedData = $request->validated();

        // Create a new company and company admin user
        return $this->registrationService->registerCompanyAdmin($validatedData);
    }

    public function sendInvite(SendInviteRequest $request): JsonResponse
    {
        // Validate the request
        $validatedData = $request->validated();

        // Send an email invitation to the employee or intern
        return $this->registrationService->sendInvite($validatedData);
    }
}

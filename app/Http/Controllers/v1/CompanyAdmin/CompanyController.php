<?php

namespace App\Http\Controllers\v1\CompanyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanyAdmin\CompanyController\UpdateCompanyInformationRequest;
use App\Http\Requests\v1\CompanyAdmin\CompanyController\UpdateCompanyScheduleRequest;
use App\Services\v1\CompanyAdmin\CompanyService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    public function show(Authenticatable $user): JsonResponse 
    {
        // Get company information for the authenticated user
        return response()->json($this->companyService->show($user));
    }

    public function updateCompanyInformation(Authenticatable $user, UpdateCompanyInformationRequest $request): JsonResponse 
    {
        // Get validated data
        $validatedData = $request->validated();

        // Update company information
        return response()->json($this->companyService->updateCompanyInformation($user, $validatedData));
    }

    public function updateCompanySchedule(Authenticatable $user, UpdateCompanyScheduleRequest $request): JsonResponse 
    {
        // Get validated data
        $validatedData = $request->validated();

        // Update company information
        return response()->json($this->companyService->updateCompanySchedule($user, $validatedData));
    }

    public function deactivateCompany(Authenticatable $user): JsonResponse 
    {
        // Deactivate company
        return response()->json($this->companyService->deactivateCompany($user));
    }
}

<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\Admin\CompanyController\IndexRequest;
use App\Http\Requests\v1\Admin\CompanyController\StoreRequest;
use App\Http\Requests\v1\Admin\CompanyController\UpdateRequest;
use App\Services\v1\Admin\CompanyService;
use Illuminate\Contracts\Auth\Authenticatable;

class CompanyController extends Controller
{
    protected CompanyService $companyService;
    protected Authenticatable $user;

    public function __construct(CompanyService $companyService, Authenticatable $user)
    {
        $this->companyService = $companyService;
        $this->user = $user;
    }

    public function index(IndexRequest $request) 
    {
        // Get validated data
        $validatedData = $request->validated();

        // Retrieve companies from database
        return $this->companyService->getCompanies($validatedData);
    }

    public function show(int $companyId) 
    {
        // Retrieve company by ID from database
        return $this->companyService->getCompanyById($companyId);
    }

    public function store(StoreRequest $request) 
    {
        // Get validated data
        $validatedData = $request->validated();

        // Create a new company in the database
        return $this->companyService->createCompany($validatedData);
    }

    public function update(UpdateRequest $request, int $companyId) 
    {
        // Get validated data
        $validatedData = $request->validate();

        // Update the company in the database
        return $this->companyService->updateCompany($validatedData, $companyId);
    }

    public function destroy(int $companyId) 
    {
        // Delete the company in the database
        return $this->companyService->deleteCompany($companyId);
    }
}

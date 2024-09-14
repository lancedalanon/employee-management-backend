<?php

namespace App\Services\v1\Admin;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CompanyService
{
    public function getCompanies(array $validatedData): JsonResponse
    {
        // Retrieve the query parameters from the request
        $sort = $validatedData['sort']; 
        $order = $validatedData['order']; 
        $search = $validatedData['search'];
        $perPage = $validatedData['per_page'];
        $page = $validatedData['page'];

        // Build the query
        $query = Company::select('company_id', 'company_name', 'company_registration_number', 'company_tax_id', 'company_phone_number', 'company_email')
                    ->whereNull('deactivated_at');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('company_id', 'LIKE', "%$search%")
                    ->orWhere('company_name', 'LIKE', "%$search%")
                    ->orWhere('company_registration_number', 'LIKE', "%$search%")
                    ->orWhere('company_tax_id', 'LIKE', "%$search%")
                    ->orWhere('company_phone_number', 'LIKE', "%$search%")
                    ->orWhere('company_email', 'LIKE', "%$search%");
            });
        }

        // Apply sorting
        $query->orderBy($sort, $order);

        // Paginate the results
        $companies = $query->paginate($perPage, ['*'], 'page', $page);

        // Construct the response data
        $responseData = [
            'message' => $companies->isEmpty() ? 'No companies found for the provided criteria.' : 'Companies retrieved successfully.',
            'data' => $companies,
        ];

        // Return the response as JSON with a 200 status code
        return response()->json($responseData, 200);
    }

    public function getCompanyById(int $companyId): JsonResponse
    {
        // Retrieve the Company for the given ID and check if it exists
        $company = Company::select('company_id', 'company_name', 'company_registration_number', 'company_tax_id', 'company_phone_number', 'company_email')
            ->where('company_id', $companyId)
            ->whereNull('deactivated_at')
            ->first();

        // Handle Company not found
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Company retrieved successfully.',
            'data' => $company,
        ], 200);
    }

    public function createCompany(array $validatedData): JsonResponse
    {
        // Create a new company with the validated data, handling nullable fields automatically
        Company::create($validatedData);

        // Return a successful response with the created company data
        return response()->json([
            'message' => 'Company created successfully.',
        ], 201);
    }

    public function updateCompany(array $validatedData, int $companyId): JsonResponse
    {
        // Retrieve the Company for the given ID and check if it exists
        $company = Company::where('company_id', $companyId)
                    ->whereNull('deactivated_at')
                    ->first();

        // Handle case where company is not found
        if (!$company) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        // Update the company attributes with validated data
        $company->fill($validatedData);

        // Check if any fields have changed using isDirty()
        if (!$company->isDirty()) {
            return response()->json(['message' => 'No changes detected.'], 400);
        }

        // Proceed with saving changes if there are updates
        $company->save();

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Company updated successfully.'], 200);
    }

    public function deleteCompany(int $companyId): JsonResponse
    {
        // Retrieve the Company for the given ID and check if it exists
        $company = Company::where('company_id', $companyId)
                    ->whereNull('deactivated_at')
                    ->first();

        // Handle case where company is not found or has been deactivated
        if (!$company) {
            return response()->json(['message' => 'Company not found or has been deactivated.'], 404);
        }

        // Deactivate the company
        $company->update(['deactivated_at' => Carbon::now()]);

        // Return the response as JSON with a 200 status code
        return response()->json(['message' => 'Company deactivated successfully.'], 200);
    }
}
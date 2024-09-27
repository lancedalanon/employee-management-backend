<?php

namespace App\Services\v1\CompanyAdmin;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;

class CompanyService
{
    public function show(Authenticatable $user): JsonResponse
    {
        // Retrieve the company record for the given ID and check if it exists
        $company = Company::where(function ($query) {
                        $query->where('deactivated_at', '<=', Carbon::now())
                            ->orWhereNull('deactivated_at');
                    })
                    ->where('company_id', $user->company_id)
                    ->first();

        // Handle company record not found
        if (!$company) {
            return response()->json(['message' => 'Company record not found.'], 404);
        }

        // Return the response as JSON with a 200 status code
        return response()->json([
            'message' => 'Company record retrieved successfully.',
            'data' => $company,
        ], 200);
    }

    public function updateCompanyInformation(Authenticatable $user, array $validatedData): JsonResponse
    {
        // Retrieve the company record for the given ID and check if it exists
        $company = Company::where(function ($query) {
                        $query->where('deactivated_at', '<=', Carbon::now())
                            ->orWhereNull('deactivated_at');
                    })
                    ->where('company_id', $user->company_id)
                    ->first();

        // Handle company record not found
        if (!$company) {
        return response()->json(['message' => 'Company record not found.'], 404);
        }

        // Update the company attributes with validated data
        $company->fill($validatedData);

        // Check if any fields have changed using isDirty()
        if (!$company->isDirty()) {
        return response()->json(['message' => 'No changes detected.'], 400);
        }

        // Save changes if dirty (changes detected)
        $company->save();

        return response()->json([
            'message' => 'Company information updated successfully.',
        ], 200);
    }

    public function updateCompanySchedule(Authenticatable $user, array $validatedData): JsonResponse 
    {
        // Retrieve the company record for the given ID and check if it exists
        $company = Company::where(function ($query) {
                        $query->where('deactivated_at', '<=', Carbon::now())
                            ->orWhereNull('deactivated_at');
                    })
                    ->where('company_id', $user->company_id)
                    ->first();

        // Handle company record not found
        if (!$company) {
            return response()->json(['message' => 'Company record not found.'], 404);
        }

        // Update the company attributes with validated data
        $company->fill($validatedData);

        // Check if any fields have changed using isDirty()
        if (!$company->isDirty()) {
            return response()->json(['message' => 'No changes detected.'], 400);
        }

        // Save changes if dirty (changes detected)
        $company->save();

        return response()->json([
            'message' => 'Company schedule updated successfully.',
        ], 200);
    }

    public function deactivateCompany(Authenticatable $user): JsonResponse 
    {
        // Retrieve the company record for the given ID and check if it exists
        $company = Company::where(function ($query) {
                        $query->where('deactivated_at', '<=', Carbon::now())
                            ->orWhereNull('deactivated_at');
                    })
                    ->where('company_id', $user->company_id)
                    ->select('company_id')
                    ->first();

        // Handle company record not found
        if (!$company) {
            return response()->json(['message' => 'Company record not found.'], 404);
        }

        // Set the deactivated_at field to 1 month from now
        $company->deactivated_at = Carbon::now()->addMonth();

        // Save the changes
        $company->save();

        return response()->json([
            'message' => 'Company successfully deactivated. Deactivation date set to 1 month from now.',
        ], 200);
    }
}
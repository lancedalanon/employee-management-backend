<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request) {
        $perPage = ($request->input('per_page', 10));
        $page = ($request->input('page', 1));

        $response = Company::paginate($perPage, ['*'], 'page', $page);

        return response()->json($response, 200);
    }

    public function show(int $companyId) {
        $response = Company::findOrFail($companyId);
        
        return response()->json($response, 200);
    }

    public function store(Request $request, int $userId) {
        $validatedData = $request->validate([
            'company_name' => 'required|string|unique|max:255',
            'company_registration_number' => 'nullable|string|unique|max:50',
            'company_tax_id' => 'nullable|string|unique|max:50',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_phone_number' => 'nullable|unique|string|max:20',
            'company_email' => 'nullable|email|unique|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_industry' => 'nullable|string|max:100',
            'company_founded_at' => 'nullable|date',
            'company_description' => 'nullable|string',
        ]);

        $validatedData['user_id'] = $userId;

        Company::create([$validatedData]);

        return response()->json([
            'message' => 'Company created successfully.'
        ], 201);
    }

    public function update(Request $request, int $companyId) {
        $company = Company::findOrFail($companyId);

        $validatedData = $request->validate([
            'company_name' => 'required|string|max:255|unique:companies,company_name,' . $companyId,
            'company_registration_number' => 'nullable|string|max:50|unique:companies,company_registration_number,' . $companyId,
            'company_tax_id' => 'nullable|string|max:50|unique:companies,company_tax_id,' . $companyId,
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_phone_number' => 'nullable|string|max:20|unique:companies,company_phone_number,' . $companyId,
            'company_email' => 'nullable|email|max:255|unique:companies,company_email,' . $companyId,
            'company_website' => 'nullable|url|max:255',
            'company_industry' => 'nullable|string|max:100',
            'company_founded_at' => 'nullable|date',
            'company_description' => 'nullable|string',
        ]);
    
        $company->update($validatedData);
    
        return response()->json([
            'message' => 'Company updated successfully.',
        ], 200);
    }

    public function destroy(int $companyId) {
        $company = Company::findOrFail($companyId);

        $company->deactivated_at = Carbon::now()->addDays(30);

        $company->save();

        return response()->json([
            'message' => 'Company deactivation set successfully.',
        ], 200);
    }
}

<?php

namespace App\Http\Requests\v1\Admin\CompanyController;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|unique:companies,company_name|max:255',
            'company_registration_number' => 'nullable|string|unique:companies,company_registration_number|max:50',
            'company_tax_id' => 'nullable|string|unique:companies,company_tax_id|max:50',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_phone_number' => 'nullable|string|unique:companies,company_phone_number|max:20',
            'company_email' => 'nullable|email|unique:companies,company_email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_industry' => 'nullable|string|max:100',
            'company_founded_at' => 'nullable|date',
            'company_description' => 'nullable|string',
        ];
    }
}

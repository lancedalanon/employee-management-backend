<?php

namespace App\Http\Requests\v1\Admin\CompanyController;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Modify this if necessary to determine authorization
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Retrieve the companyId from the route parameters
        $companyId = $this->route('companyId');


        return [
            'company_name' => 'required|string|max:255|unique:companies,company_name,'.$companyId.',company_id',
            'company_registration_number' => 'nullable|string|max:50|unique:companies,company_registration_number,'.$companyId.',company_id',
            'company_tax_id' => 'nullable|string|max:50|unique:companies,company_tax_id,'.$companyId.',company_id',
            'company_address' => 'nullable|string|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_state' => 'nullable|string|max:100',
            'company_postal_code' => 'nullable|string|max:20',
            'company_country' => 'nullable|string|max:100',
            'company_phone_number' => 'nullable|string|max:20|unique:companies,company_phone_number,'.$companyId.',company_id',
            'company_email' => 'nullable|email|max:255|unique:companies,company_email,'.$companyId.',company_id',
            'company_website' => 'nullable|url|max:255',
            'company_industry' => 'nullable|string|max:100',
            'company_founded_at' => 'nullable|date',
            'company_description' => 'nullable|string',
        ];
    }
}

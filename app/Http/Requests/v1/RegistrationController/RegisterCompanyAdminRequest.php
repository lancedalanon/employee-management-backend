<?php

namespace App\Http\Requests\v1\RegistrationController;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyAdminRequest extends FormRequest
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
            // Company Admin
            'first_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'middle_name' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'last_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'suffix' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',  
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:13|unique:users,phone_number',
            'password' => 'required|string|min:8|max:255|confirmed',

            // Company
            'company_name' => 'required|string|unique:companies,company_name|max:255',
            'company_registration_number' => 'required|string|unique:companies,company_registration_number|max:50',
            'company_tax_id' => 'required|string|unique:companies,company_tax_id|max:50',
            'company_address' => 'required|string|max:255',
            'company_city' => 'required|string|max:100',
            'company_state' => 'required|string|max:100',
            'company_postal_code' => 'required|string|max:20',
            'company_country' => 'required|string|max:100',
            'company_phone_number' => 'required|string|unique:companies,company_phone_number|max:20',
            'company_email' => 'required|email|unique:companies,company_email|max:255',
            'company_website' => 'required|url|max:255',
            'company_industry' => 'required|string|max:100',
            'company_founded_at' => 'required|date|before_or_equal:today',
            'company_description' => 'nullable|string|max:500',
        ];
    }
}

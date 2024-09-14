<?php

namespace App\Http\Requests\v1\CompanyAdmin\AttendanceController;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
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
            'sort' => 'in:user_id,first_name,middle_name,last_name,suffix,email,phone_number',
            'order' => 'in:asc,desc',
            'search' => 'nullable|string|max:255',
            'per_page' => 'int|min:5|max:50',
            'page' => 'int|min:1',
            'employment_type' => 'required|in:full_time,part_time',
            'role' => 'required|in:intern,employee,company_admin,company_supervisor',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort.in' => 'Please choose a valid field to sort by (user_id, first_name, middle_name, last_name, suffix, email, phone_number)',
            'order.in' => 'The order must be either ascending (asc) or descending (desc).',
            'search.string' => 'The search term must be valid.',
            'search.max' => 'The search term may not be greater than 255 characters.',
            'per_page.integer' => 'The per page value must be a valid number from 1 and above.',
            'per_page.min' => 'The per page value must be at least :min items.',
            'per_page.max' => 'The per page value may not be greater than :max items.',
            'page.integer' => 'The page number must be a valid number from 1 and above.',
            'page.min' => 'The page number must be at least :min.',
        ];
    }

    /**
     * Modify the input data before validation.
     *
     * @return array
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'sort' => $this->input('sort', 'user_id'), // Default sort by 'user_id'
            'order' => $this->input('order', 'desc'), // Default order is 'desc'
            'per_page' => $this->input('per_page', 25), // Default items per page
            'page' => $this->input('page', 1), // Default page number
            'search' => $this->input('search', ''), // Default search term is empty string
        ]);
    }
}

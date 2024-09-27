<?php

namespace App\Http\Requests\v1\CompanyAdmin\CompanyController;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyScheduleRequest extends FormRequest
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
            'company_full_time_start_time' => 'nullable|date_format:H:i:s',
            'company_full_time_end_time' => 'nullable|required_with:company_full_time_start_time|date_format:H:i:s',
            'company_part_time_start_time' => 'nullable|date_format:H:i:s',
            'company_part_time_end_time' => 'nullable|required_with:company_part_time_start_time|date_format:H:i:s',
        ];
    }
}

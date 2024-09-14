<?php

namespace App\Http\Requests\v1\CompanyAdmin\AttendanceController;

use Illuminate\Foundation\Http\FormRequest;

class ShowAttendanceRequest extends FormRequest
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
            'employment_type' => 'required|in:full-time,part-time',
            'role' => 'required|in:intern,employee,company_admin,company_supervisor',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}

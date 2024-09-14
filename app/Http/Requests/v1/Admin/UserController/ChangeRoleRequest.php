<?php

namespace App\Http\Requests\v1\Admin\UserController;

use Illuminate\Foundation\Http\FormRequest;

class ChangeRoleRequest extends FormRequest
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
            'role' => 'required|string|in:intern,employee,admin,company_admin,company_supervisor,admin',
            'employment_type' => [
                'nullable', 
                'string', 
                'in:full_time,part_time', 
                'required_if:role,intern,employee,company_admin,company_supervisor'
            ],
            'shift' => [
                'nullable', 
                'string', 
                'in:day_shift,afternoon_shift,evening_shift,early_shift,late_shift,night_shift', 
                'required_if:role,intern,employee,company_admin,company_supervisor'
            ],
        ];
    }
}

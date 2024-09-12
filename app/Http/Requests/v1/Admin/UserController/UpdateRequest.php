<?php

namespace App\Http\Requests\v1\Admin\UserController;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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
        $userId = $this->route('userId');

        return [
            'first_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'middle_name' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'last_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'suffix' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:Male,Female',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId)
            ],
            'phone_number' => [
                'required',
                'string',
                'max:13',
                Rule::unique('users', 'phone_number')->ignore($userId)
            ],
            'password' => 'required|string|min:8|max:255|confirmed',
            'role' => 'nullable|string|in:intern,employee,admin,company_admin,company_supervisor',
            'company_id' => 'nullable|exists:companies,company_id',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
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

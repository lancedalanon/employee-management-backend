<?php

namespace App\Http\Requests\v1\RegistrationController;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'middle_name' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'last_name' => 'required|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',
            'suffix' => 'nullable|string|max:255|regex:/^[\p{L}\s\-\'\.]*$/u',  
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:Male,Female',
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'required|string|max:13|unique:users,phone_number',
            'password' => 'required|string|min:8|max:255|confirmed',
            'employment_type' => 'required|string|in:full_time,part_time',
            'shift' => 'required|string|in:day_shift,afternoon_shift,evening_shift,early_shift,late_shift,night_shift',
            'role' => 'required|string|in:intern,employee',
            'token' => 'required|string|max:255',
        ];
    }
}

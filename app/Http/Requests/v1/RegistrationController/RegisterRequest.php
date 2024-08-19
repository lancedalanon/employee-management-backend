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
            'first_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]*$/',
            'middle_name' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]*$/',
            'last_name' => 'required|string|max:255|regex:/^[a-zA-Z\s]*$/',
            'suffix' => 'nullable|string|max:255|regex:/^[a-zA-Z\s]*$/',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:Male,Female',
            'username' => 'required|string|max:255|unique:users,username',
            'phone_number' => 'required|string|max:13|unique:users,phone_number',
            'password' => 'required|string|min:8|max:255|confirmed',
            'employment_type' => 'required|string|in:full-time,part-time',
            'shift' => 'required_if:employment_type,part-time|string|in:day-shift,afternoon-shift,evening-shift,early-shift,late-shift',
            'role' => 'required|string|in:intern,employee',
            'token' => 'required|string|max:255',
        ];
    }
}

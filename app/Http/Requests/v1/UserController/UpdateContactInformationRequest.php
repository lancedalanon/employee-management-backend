<?php

namespace App\Http\Requests\v1\UserController;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactInformationRequest extends FormRequest
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
            'username' => [
                'required',
                'string',
                'max:255',
                // Allow existing username for the current user
                'unique:users,username,' . $this->user_id,
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                // Allow existing email for the current user
                'unique:users,email,' . $this->user_id,
            ],
            'recovery_email' => [
                'nullable',
                'string',
                'max:255',
                // Allow existing recovery email for the current user
                'unique:users,recovery_email,' . $this->user_id,
            ],
            'phone_number' => [
                'required',
                'string',
                'max:13',
                // Allow existing phone number for the current user
                'unique:users,phone_number,' . $this->user_id,
            ],
            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]*$/',
            ],
            'emergency_contact_number' => [
                'nullable',
                'string',
                'max:13',
                // Only required if emergency_contact_name is present
                'required_if:emergency_contact_name,!=,',
                // Allow existing emergency contact number for the current user
                'unique:users,emergency_contact_number,' . $this->user_id,
            ],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emergency_contact_number.required_if' => 'Emergency contact number is required when emergency contact name is present.',
        ];
    }
}

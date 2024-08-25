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
        // Fetch the authenticated user's ID
        $userId = $this->user()->user_id;

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                // Allow existing username for the current user, using user_id as the primary key
                'unique:users,username,' . $userId . ',user_id',
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                'email',
                // Allow existing email for the current user, using user_id as the primary key
                'unique:users,email,' . $userId . ',user_id',
            ],
            'recovery_email' => [
                'nullable',
                'string',
                'max:255',
                'email',
                // Allow existing recovery email for the current user, using user_id as the primary key
                'unique:users,recovery_email,' . $userId . ',user_id',
            ],
            'phone_number' => [
                'required',
                'string',
                'max:13',
                // Allow existing phone number for the current user, using user_id as the primary key
                'unique:users,phone_number,' . $userId . ',user_id',
            ],
            'emergency_contact_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]*$/u',
            ],
            'emergency_contact_number' => [
                'nullable',
                'string',
                'max:13',
                // Only required if emergency_contact_name is present
                'required_if:emergency_contact_name,!=,',
                // Allow existing emergency contact number for the current user, using user_id as the primary key
                'unique:users,emergency_contact_number,' . $userId . ',user_id',
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

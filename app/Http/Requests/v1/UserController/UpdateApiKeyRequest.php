<?php

namespace App\Http\Requests\v1\UserController;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiKeyRequest extends FormRequest
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
            'api_key' => 'required|min:32|max:500'
        ];
    }

        /**
     * Get custom error messages for validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'api_key.required' => 'API key is required.',
            'api_key.min' => 'API key must be at least 32 characters long.',
            'api_key.max' => 'API key cannot exceed 500 characters.',
        ];
    }
}

<?php

namespace App\Http\Requests\v1\UserController;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalInformationRequest extends FormRequest
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
        ];
    }
}

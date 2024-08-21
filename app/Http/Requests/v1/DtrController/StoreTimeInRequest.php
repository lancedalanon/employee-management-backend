<?php

namespace App\Http\Requests\v1\DtrController;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeInRequest extends FormRequest
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
            'dtr_time_in_image' => 'required|file|image|mimes:jpeg,jpg,png|max:5120',
        ];
    }

    /**
     * Get custom validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dtr_time_in_image.required' => 'Please upload an image for your time-in.',
            'dtr_time_in_image.file' => 'The file you uploaded is not valid.',
            'dtr_time_in_image.image' => 'The file must be an image (JPG, JPEG, or PNG).',
            'dtr_time_in_image.mimes' => 'The image must be in JPG, JPEG, or PNG format.',
            'dtr_time_in_image.max' => 'The image size must be 5 MB or less.',
        ];
    }
}

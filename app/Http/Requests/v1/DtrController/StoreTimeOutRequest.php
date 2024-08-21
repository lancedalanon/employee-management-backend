<?php

namespace App\Http\Requests\v1\DtrController;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeOutRequest extends FormRequest
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
            'dtr_time_out_image' => 'required|file|image|mimes:jpeg,jpg,png|max:5120',
            'end_of_the_day_report_images' => 'required|array|max:4',
            'end_of_the_day_report_images.*' => 'file|image|mimes:jpeg,jpg,png|max:5120',
            'dtr_end_of_the_day_report' => 'required|string|max:255',
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
            'dtr_time_out_image.required' => 'Please upload an image for your time out.',
            'dtr_time_out_image.file' => 'The time out file must be a valid file.',
            'dtr_time_out_image.image' => 'The time out file must be an image (JPG, JPEG, or PNG).',
            'dtr_time_out_image.mimes' => 'The time out image must be in JPG, JPEG, or PNG format.',
            'dtr_time_out_image.max' => 'The time out image size must be 5 MB or less.',

            'end_of_the_day_report_images.required' => 'Please upload at least one end of the day report image.',
            'end_of_the_day_report_images.array' => 'The end of the day report images must be an array.',
            'end_of_the_day_report_images.max' => 'You can upload a maximum of 4 end of the day report images.',
            'end_of_the_day_report_images.*.file' => 'Each report image must be a valid file.',
            'end_of_the_day_report_images.*.image' => 'Each report image must be an image (JPG, JPEG, or PNG).',
            'end_of_the_day_report_images.*.mimes' => 'Each report image must be in JPG, JPEG, or PNG format.',
            'end_of_the_day_report_images.*.max' => 'Each report image size must be 5 MB or less.',

            'dtr_end_of_the_day_report.required' => 'The end of the day report is required.',
            'dtr_end_of_the_day_report.string' => 'The end of the day report must be a valid string.',
            'dtr_end_of_the_day_report.max' => 'The end of the day report must not exceed 255 characters.',
        ];
    }
}

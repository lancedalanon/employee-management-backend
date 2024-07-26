<?php

namespace App\Http\Requests\Dtr;

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
            'end_of_the_day_report' => 'required|string|max:500',
            'end_of_the_day_report_images' => 'required|array|max:4',
            'end_of_the_day_report_images.*' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048'
        ];
    }
}

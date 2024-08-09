<?php

namespace App\Http\Requests\LeaveRequest;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreRequest extends FormRequest
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
            'absence_start_date' => 'required|date|after:today',
            'absence_end_date' => 'required|date|after_or_equal:absence_start_date|before:9999-01-01',
            'absence_reason' => 'required|string|max:255',
        ];
    }
}

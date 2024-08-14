<?php

namespace App\Http\Requests\Admin\Attendace;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRequest extends FormRequest
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
            'employment_status' =>'required|in:full-time,part-time',
            'personnel' => 'required|in:employee,intern',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}

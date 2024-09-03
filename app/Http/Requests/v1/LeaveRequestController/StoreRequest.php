<?php

namespace App\Http\Requests\v1\LeaveRequestController;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
            'dtr_absence_date' => 'required|date',
            'dtr_absence_reason' => 'required|string|max:255',
        ];
    }
}

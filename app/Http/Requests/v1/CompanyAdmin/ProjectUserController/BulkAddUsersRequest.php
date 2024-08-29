<?php

namespace App\Http\Requests\v1\CompanyAdmin\ProjectUserController;

use Illuminate\Foundation\Http\FormRequest;

class BulkAddUsersRequest extends FormRequest
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
            'project_users' => 'required|array',
            'project_users.*' => 'required|integer|exists:users,user_id',
        ];
    }
}

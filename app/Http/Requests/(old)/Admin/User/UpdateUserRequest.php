<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    protected $userId;

    public function __construct()
    {
        $this->userId = $this->route('userId');
        parent::__construct();
    }

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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'place_of_birth' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($this->userId, 'user_id'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId, 'user_id'),
            ],
            'password' => 'nullable|string|min:8|confirmed', // Password is optional for update
            'role' => 'nullable|string|in:employee,intern', // Ensure only 'employee' or 'intern'
            'employment_type' => 'nullable|string|in:full-time,part-time', // Employment type
            'shift' => 'nullable|string|in:day-shift,afternoon-shift,evening-shift,early-shift,late-shift', // Work shift
        ];
    }
}

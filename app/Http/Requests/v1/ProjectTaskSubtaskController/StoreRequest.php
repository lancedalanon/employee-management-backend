<?php

namespace App\Http\Requests\v1\ProjectTaskSubtaskController;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'project_task_subtask_name' => 'required|max:255',
            'project_task_subtask_description' => 'nullable|max:500',
            'project_task_subtask_progress' => [
                'required',
                Rule::in(config('constants.project_task_subtask_progress')),
            ],
            'project_task_subtask_priority_level' => [
                'required',
                Rule::in(config('constants.project_task_subtask_priority_level')),
            ],
        ];
    }
}

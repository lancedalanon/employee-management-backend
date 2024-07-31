<?php

namespace App\Http\Requests\ProjectTask;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    protected $validProjectTaskProgress;

    public function __construct()
    {
        $this->validProjectTaskProgress = config('constants.project_task_progress');
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
            'project_task_name' => 'required|string|max:255',
            'project_task_description' => 'required|string',
            'project_task_progress' => 'required|in:' . implode(',', $this->validProjectTaskProgress),
            'project_task_priority_level' => 'required|string|in:Low,Medium,High',
        ];
    }
}

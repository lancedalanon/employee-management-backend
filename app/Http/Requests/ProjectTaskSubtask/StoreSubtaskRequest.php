<?php

namespace App\Http\Requests\ProjectTaskSubtask;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubtaskRequest extends FormRequest
{
    protected $validProjectTaskProgress;

    protected $projectTaskPriorityLevel;

    public function __construct()
    {
        $this->validProjectTaskProgress = config('constants.project_task_progress');
        $this->projectTaskPriorityLevel = config('constants.project_task_priority_level');
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
            'project_task_subtask_name' => 'required|string|max:255',
            'project_task_subtask_description' => 'required|string',
            'project_task_subtask_progress' => 'required|in:'.implode(',', $this->validProjectTaskProgress),
            'project_task_subtask_priority_level' => 'required|in:'.implode(',', $this->projectTaskPriorityLevel),
        ];
    }
}

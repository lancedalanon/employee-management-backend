<?php

namespace App\Http\Requests\ProjectTaskSubtaskStatus;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusRequest extends FormRequest
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
            'project_task_subtask_status' => 'required|string|max:255',
            'project_task_subtask_status_media_file' => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,avi,mov,wmv|max:20480',
        ];
    }
}

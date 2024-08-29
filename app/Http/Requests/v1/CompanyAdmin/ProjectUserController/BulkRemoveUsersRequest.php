<?php

namespace App\Http\Requests\v1\CompanyAdmin\ProjectUserController;

use Illuminate\Foundation\Http\FormRequest;

class BulkRemoveUsersRequest extends FormRequest
{
    protected $projectId;

    /**
     * Create a new request instance.
     *
     * @param int $projectId
     */
    public function __construct(int $projectId)
    {
        parent::__construct();
        $this->projectId = $projectId;
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
            'project_users' => 'required|array',
            'project_users.*' => 'required|integer|exists:project_users,user_id,project_id,' . $this->projectId,
        ];
    }
}

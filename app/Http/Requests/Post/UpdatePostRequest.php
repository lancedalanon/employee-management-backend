<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'post_title' => 'required|string|max:255',
            'post_content' => 'required|string',
            'post_slug' => 'required|string|max:255',
            'post_tags' => 'required|array',
            'post_tags.*' => 'string|max:50',
            'post_media' => 'nullable|array',
            'post_media.*' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/gif,image/svg,video/mp4,video/quicktime|max:204800',
        ];
    }
}

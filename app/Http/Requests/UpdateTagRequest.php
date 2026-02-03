<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
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
        $tagId = $this->route('tag') ? $this->route('tag')->id : null;
        return [
            'name' => 'sometimes|required|string|max:255|unique:tags,name,' . $tagId,
            'slug' => 'sometimes|nullable|string|max:255|unique:tags,slug,' . $tagId,
            'is_active' => 'nullable|boolean',
            'tag_type' => 'sometimes|required|string|in:company,customer,product',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

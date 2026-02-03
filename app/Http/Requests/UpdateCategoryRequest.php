<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category') ? $this->route('category')->id : null;
        return [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $categoryId,
            'slug' => 'sometimes|nullable|string|max:255|unique:categories,slug,' . $categoryId,
            'category_type' => 'sometimes|required|string|in:company,customer,product',
            'is_active' => 'nullable|boolean',
            'icon' => 'nullable|file|mimes:svg|max:100',
            'remove_icon' => 'nullable|boolean',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

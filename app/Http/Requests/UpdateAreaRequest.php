<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends FormRequest
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
        $areaId = $this->route('area') ? $this->route('area')->id : null;
        return [
            'name_ar' => 'sometimes|required|string|max:255|unique:areas,name_ar,' . $areaId,
            'name_en' => 'sometimes|required|string|max:255|unique:areas,name_en,' . $areaId,
            'is_active' => 'nullable|boolean',
            'district_id' => 'sometimes|required|exists:districts,id',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

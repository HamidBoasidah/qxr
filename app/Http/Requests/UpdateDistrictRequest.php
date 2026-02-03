<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDistrictRequest extends FormRequest
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
        $districtId = $this->route('district') ? $this->route('district')->id : null;
        return [
            'name_ar' => 'sometimes|required|string|max:255|unique:districts,name_ar,' . $districtId,
            'name_en' => 'sometimes|required|string|max:255|unique:districts,name_en,' . $districtId,
            'is_active' => 'nullable|boolean',
            'governorate_id' => 'sometimes|required|exists:governorates,id',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

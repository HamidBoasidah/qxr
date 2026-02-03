<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGovernorateRequest extends FormRequest
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
        $governorateId = $this->route('governorate') ? $this->route('governorate')->id : null;
        return [
            'name_ar' => 'sometimes|required|string|max:255|unique:governorates,name_ar,' . $governorateId,
            'name_en' => 'sometimes|required|string|max:255|unique:governorates,name_en,' . $governorateId,
            'is_active' => 'nullable|boolean',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

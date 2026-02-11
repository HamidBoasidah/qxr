<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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
            'label' => 'nullable|string|max:50',
            'address' => 'required|string|max:1000',
            'governorate_id' => 'nullable|exists:governorates,id',
            'district_id' => 'nullable|exists:districts,id',
            'area_id' => 'nullable|exists:areas,id',
            'lat' => 'nullable|numeric|between:-90,90',
            'lang' => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'label' => __('validation.attributes.label'),
            'address' => __('validation.attributes.address'),
            'governorate_id' => __('validation.attributes.governorate'),
            'district_id' => __('validation.attributes.district'),
            'area_id' => __('validation.attributes.area'),
            'lat' => __('validation.attributes.latitude'),
            'lang' => __('validation.attributes.longitude'),
            'is_default' => __('validation.attributes.is_default'),
            'is_active' => __('validation.attributes.is_active'),
        ];
    }
}

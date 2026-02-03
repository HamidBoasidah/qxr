<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // للسماح مؤقتًا
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required','string','max:255',
                'unique:roles,name,' . $this->route('role'),
            ],
            'display_name' => ['nullable','array'],
            'display_name.en' => ['nullable','string','max:255'],
            'display_name.ar' => ['nullable','string','max:255'],

            'permissions' => ['array'],
            'permissions.*' => ['string','exists:permissions,name'],
        ];
    }

}

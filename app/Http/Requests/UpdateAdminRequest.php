<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = optional($this->route('admin'))->id ?? $this->route('admin');

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|unique:admins,email,' . $adminId,
            'avatar' => 'nullable|file|image|max:2048',
            'phone_number' => ['nullable', 'regex:/^\d{9}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^\d{9}$/'],
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'facebook' => 'nullable|url',
            'x_url' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'instagram' => 'nullable|url',
            'is_active' => 'nullable|boolean',
            'role_id' => 'sometimes|exists:roles,id',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

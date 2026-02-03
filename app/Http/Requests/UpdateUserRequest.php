<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        $userId = optional($this->route('user'))->id ?? $this->route('user');

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $userId,
            'avatar' => 'nullable|file|image|max:2048',
            'phone_number' => ['nullable', 'regex:/^\\d{9,15}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^\\d{9,15}$/'],
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'facebook' => 'nullable|url',
            'x_url' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'instagram' => 'nullable|url',
            'is_active' => 'nullable|boolean',
            'locale' => 'nullable|string|max:10',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}

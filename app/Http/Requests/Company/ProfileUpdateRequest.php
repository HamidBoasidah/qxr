<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
        $user = $this->user('web');
        
        return [
            // User fields
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|file|image|max:2048',
            'phone_number' => ['nullable', 'regex:/^\d{9,15}$/'],
            'whatsapp_number' => ['nullable', 'regex:/^\d{9,15}$/'],
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8',
            'facebook' => 'nullable|url',
            'x_url' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'instagram' => 'nullable|url',
            
            // Company-specific fields
            'company_name' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'logo' => 'nullable|file|image|max:2048',
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة السر الحالية مطلوبة',
            'current_password.current_password' => 'كلمة السر الحالية غير صحيحة',
            'new_password.required' => 'كلمة السر الجديدة مطلوبة',
            'new_password.confirmed' => 'تأكيد كلمة السر غير متطابق',
            'new_password.min' => 'كلمة السر يجب أن تكون 8 أحرف على الأقل',
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
            'current_password' => 'كلمة السر الحالية',
            'new_password' => 'كلمة السر الجديدة',
            'new_password_confirmation' => 'تأكيد كلمة السر',
        ];
    }
}

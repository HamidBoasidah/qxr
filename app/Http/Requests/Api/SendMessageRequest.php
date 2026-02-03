<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'body' => ['nullable', 'string', 'max:10000'],
            'files' => ['sometimes', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240'], // 10MB max per file
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'body.max' => 'الرسالة طويلة جداً',
            'files.max' => 'لا يمكن إرسال أكثر من 10 ملفات',
            'files.*.max' => 'حجم الملف كبير جداً (الحد الأقصى 10 ميجابايت)',
        ];
    }
}

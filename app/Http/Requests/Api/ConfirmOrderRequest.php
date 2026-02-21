<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Check user is authenticated and has customer role.
     * 
     * Requirements: 3.1, 3.2, 3.6
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->user_type === 'customer';
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Requirements: 2.2
     */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string', 'regex:/^PV-\d{8}-[A-Z0-9]{4}$/'],
        ];
    }
}

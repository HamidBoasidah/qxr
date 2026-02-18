<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,approved,delivered,cancelled'],
            'notes_customer' => ['nullable', 'string', 'max:1000'],
            'notes_company' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

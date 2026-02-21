<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PreviewOrderRequest extends FormRequest
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
     * Requirements: 1.2, 4.1, 4.3, 4.7
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Configure the validator instance.
     * 
     * Requirements: 4.8
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate product_ids
            $productIds = collect($this->items)->pluck('product_id');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('items', 'Duplicate products are not allowed');
            }
        });
    }
}

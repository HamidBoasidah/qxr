<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Requirements: 2.1, 2.2
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->user_type === 'customer';
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Requirements: 3.1, 3.2, 3.3, 3.6, 3.8
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            
            'order_items' => ['required', 'array', 'min:1'],
            'order_items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'order_items.*.qty' => ['required', 'integer', 'min:1'],
            'order_items.*.unit_price_snapshot' => ['required', 'numeric', 'min:0'],
            'order_items.*.discount_amount_snapshot' => ['required', 'numeric', 'min:0'],
            'order_items.*.final_line_total_snapshot' => ['required', 'numeric', 'min:0'],
            'order_items.*.selected_offer_id' => ['nullable', 'integer', 'exists:offers,id'],
            
            'order_item_bonuses' => ['nullable', 'array'],
            'order_item_bonuses.*.order_item_index' => ['required', 'integer', 'min:0'],
            'order_item_bonuses.*.bonus_product_id' => ['required', 'integer', 'exists:products,id'],
            'order_item_bonuses.*.bonus_qty' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Configure the validator instance.
     * 
     * Requirements: 3.7, 7.1
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate product_ids
            $productIds = collect($this->order_items)->pluck('product_id');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('order_items', 'Duplicate products are not allowed');
            }
            
            // Validate order_item_index references
            if ($this->order_item_bonuses) {
                $maxIndex = count($this->order_items) - 1;
                $bonusIndexes = [];
                
                foreach ($this->order_item_bonuses as $index => $bonus) {
                    // Check if index is within bounds
                    if ($bonus['order_item_index'] > $maxIndex) {
                        $validator->errors()->add(
                            'order_item_bonuses',
                            "Invalid order_item_index: {$bonus['order_item_index']}"
                        );
                    }
                    
                    // Check for duplicate bonuses for same item
                    $itemIndex = $bonus['order_item_index'];
                    if (isset($bonusIndexes[$itemIndex])) {
                        $validator->errors()->add(
                            "order_item_bonuses.{$index}",
                            "Multiple bonuses for the same order item are not allowed"
                        );
                    }
                    $bonusIndexes[$itemIndex] = true;
                }
            }
        });
    }
}

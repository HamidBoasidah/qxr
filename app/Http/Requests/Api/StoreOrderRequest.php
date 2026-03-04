<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Requirements: 2.1, 2.2
     */
    public function authorize(): bool
    {
        // Use the FormRequest user() helper to satisfy static analysis and runtime
        return $this->user() && $this->user()->user_type === 'customer';
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
            'status' => ['nullable', 'string', 'in:draft,pending'],
            'notes_customer' => ['nullable', 'string', 'max:1000'],
            
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
            $orderItems = $this->input('order_items', []);
            $productIds = collect($orderItems)->pluck('product_id');
            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('order_items', 'Duplicate products are not allowed');
            }

            // Validate order_item_index references
            $orderItemBonuses = $this->input('order_item_bonuses', []);
            if (!empty($orderItemBonuses)) {
                $maxIndex = count($orderItems) - 1;
                $bonusIndexes = [];

                foreach ($orderItemBonuses as $index => $bonus) {
                    // Check if index is within bounds
                    if (($bonus['order_item_index'] ?? -1) > $maxIndex) {
                        $validator->errors()->add(
                            'order_item_bonuses',
                            "Invalid order_item_index: {$bonus['order_item_index']}"
                        );
                    }

                    // Check for duplicate bonuses for same item
                    $itemIndex = $bonus['order_item_index'] ?? null;
                    if ($itemIndex !== null && isset($bonusIndexes[$itemIndex])) {
                        $validator->errors()->add(
                            "order_item_bonuses.{$index}",
                            "Multiple bonuses for the same order item are not allowed"
                        );
                    }
                    if ($itemIndex !== null) {
                        $bonusIndexes[$itemIndex] = true;
                    }
                }
            }
        });
    }
}

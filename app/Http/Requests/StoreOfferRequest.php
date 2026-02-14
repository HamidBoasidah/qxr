<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class StoreOfferRequest extends BaseOfferRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope'       => ['sometimes', 'in:public,private'],
            'status'      => ['sometimes', 'in:draft,active,paused'],
            'start_at'    => ['nullable', 'date'],
            'end_at'      => ['nullable', 'date', 'after_or_equal:start_at'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:products,id'],
            'items.*.min_qty'         => ['sometimes', 'integer', 'min:1'],
            'items.*.reward_type'     => ['required', 'in:discount_percent,discount_fixed,bonus_qty'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'items.*.discount_fixed'   => ['nullable', 'numeric', 'min:0.01'],
            'items.*.bonus_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.bonus_qty'        => ['nullable', 'integer', 'min:1'],
            'targets'                => ['nullable', 'array'],
            'targets.*.target_type'  => ['required_with:targets', 'in:customer,customer_category,customer_tag'],
            'targets.*.target_id'    => ['required_with:targets', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'  => 'عنوان العرض مطلوب.',
            'scope.in'        => 'قيمة scope غير صحيحة.',
            'status.in'       => 'قيمة status غير صحيحة.',
            'end_at.after_or_equal' => 'تاريخ نهاية العرض يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'items.required'  => 'عناصر العرض مطلوبة.',
            'items.array'     => 'عناصر العرض يجب أن تكون مصفوفة.',
            'items.min'       => 'يجب إضافة عنصر واحد على الأقل للعرض.',
            'items.*.product_id.required' => 'المنتج مطلوب لكل عنصر.',
            'items.*.product_id.exists'   => 'أحد المنتجات غير موجود.',
            'items.*.reward_type.in'      => 'نوع المكافأة غير صحيح.',
            'targets.array'   => 'المستهدفون يجب أن يكونوا مصفوفة.',
            'targets.*.target_type.in' => 'نوع الاستهداف غير صحيح.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scope'  => $this->input('scope', 'public'),
            'status' => $this->input('status', 'draft'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('scope') === 'private') {
                $targets = $this->input('targets', []);
                if (empty($targets) || !is_array($targets) || count($targets) < 1) {
                    $v->errors()->add('targets', 'يجب تحديد مستهدف واحد على الأقل لأن العرض خاص (private).');
                }
            }

            $items = $this->input('items', []);
            if (is_array($items) && !empty($items)) {
                $this->validateRewardTypes($v, $items);
                $this->validateProductOwnership($v, $items);
            }

            $targets = $this->input('targets', []);
            if (is_array($targets) && !empty($targets)) {
                $this->validateTargets($v, $targets);
            }
        });
    }

    public function validatedPayload(): array
    {
        return $this->only(['title', 'description', 'scope', 'status', 'start_at', 'end_at']);
    }

    public function itemsPayload(): array
    {
        $items = $this->input('items', []) ?? [];
        if (!is_array($items)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'product_id'       => $item['product_id'],
                'min_qty'          => $item['min_qty'] ?? 1,
                'reward_type'      => $item['reward_type'],
                'discount_percent' => $item['discount_percent'] ?? null,
                'discount_fixed'   => $item['discount_fixed'] ?? null,
                'bonus_product_id' => $item['bonus_product_id'] ?? null,
                'bonus_qty'        => $item['bonus_qty'] ?? null,
            ];
        }, $items);
    }

    public function targetsPayload(): array
    {
        $targets = $this->input('targets', []) ?? [];
        if (!is_array($targets)) {
            return [];
        }

        return array_map(function ($target) {
            return [
                'target_type' => $target['target_type'],
                'target_id'   => $target['target_id'],
            ];
        }, $targets);
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateOfferRequest extends BaseOfferRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'scope'       => ['sometimes', 'in:public,private'],
            'status'      => ['sometimes', 'in:draft,active,paused'],
            'start_at'    => ['sometimes', 'nullable', 'date'],
            'end_at'      => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
            'items'                   => ['sometimes', 'array'],
            'items.*.product_id'      => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.min_qty'         => ['sometimes', 'integer', 'min:1'],
            'items.*.reward_type'     => ['required_with:items', 'in:discount_percent,discount_fixed,bonus_qty'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'items.*.discount_fixed'   => ['nullable', 'numeric', 'min:0.01'],
            'items.*.bonus_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.bonus_qty'        => ['nullable', 'integer', 'min:1'],
            'targets'                => ['sometimes', 'array'],
            'targets.*.target_type'  => ['required_with:targets', 'in:customer,customer_category,customer_tag'],
            'targets.*.target_id'    => ['required_with:targets', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'scope.in'        => 'قيمة scope غير صحيحة.',
            'status.in'       => 'قيمة status غير صحيحة.',
            'end_at.after_or_equal' => 'تاريخ نهاية العرض يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'items.array'     => 'عناصر العرض يجب أن تكون مصفوفة.',
            'items.*.product_id.required_with' => 'المنتج مطلوب لكل عنصر.',
            'items.*.product_id.exists'        => 'أحد المنتجات غير موجود.',
            'items.*.reward_type.in'           => 'نوع المكافأة غير صحيح.',
            'targets.array'   => 'المستهدفون يجب أن يكونوا مصفوفة.',
            'targets.*.target_type.in' => 'نوع الاستهداف غير صحيح.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->has('scope') && $this->input('scope') === 'private') {
                if (!$this->has('targets')) {
                    $v->errors()->add('targets', 'عند تحويل العرض إلى private يجب إرسال targets ضمن نفس الطلب.');
                } else {
                    $targets = $this->input('targets', []);
                    if (empty($targets) || !is_array($targets) || count($targets) < 1) {
                        $v->errors()->add('targets', 'يجب تحديد مستهدف واحد على الأقل لأن العرض خاص (private).');
                    }
                }
            }

            if ($this->has('items')) {
                $items = $this->input('items', []);
                if (is_array($items) && !empty($items)) {
                    $this->validateRewardTypes($v, $items);
                    $this->validateProductOwnership($v, $items);
                }
            }

            if ($this->has('targets')) {
                $targets = $this->input('targets', []);
                if (is_array($targets) && !empty($targets)) {
                    $this->validateTargets($v, $targets);
                }
            }
        });
    }

    public function validatedPayload(): array
    {
        return $this->only(['title', 'description', 'scope', 'status', 'start_at', 'end_at']);
    }

    public function itemsPayloadOrNull(): ?array
    {
        if (!$this->has('items')) {
            return null;
        }

        $items = $this->input('items', []);
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

    public function targetsPayloadOrNull(): ?array
    {
        if (!$this->has('targets')) {
            return null;
        }

        $targets = $this->input('targets', []);
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

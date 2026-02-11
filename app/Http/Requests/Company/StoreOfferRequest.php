<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        // لاحقًا تقدر تربطها بسياسات (Policy) أو صلاحيات
        return true;
    }

    public function rules(): array
    {
        return [
            // الشركة نأخذها من auth()->id() داخل السيرفس (لا تُرسل من العميل)
            // 'company_user_id' => 'prohibited',

            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'scope'       => ['sometimes', 'in:public,private'],
            'status'      => ['sometimes', 'in:draft,active,paused'],

            'start_at'    => ['nullable', 'date'],
            'end_at'      => ['nullable', 'date', 'after_or_equal:start_at'],

            // items
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:products,id'],
            'items.*.min_qty'         => ['sometimes', 'integer', 'min:1'],
            'items.*.reward_type'     => ['required', 'in:discount_percent,discount_fixed,bonus_qty'],

            // هذه الحقول سيتم ضبط إلزاميتها بـ withValidator حسب reward_type
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'items.*.discount_fixed'   => ['nullable', 'numeric', 'min:0.01'],
            'items.*.bonus_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.bonus_qty'        => ['nullable', 'integer', 'min:1'],

            // targets
            'targets'                => ['nullable', 'array'],
            'targets.*.target_type'  => ['required_with:targets', 'in:customer,customer_category,customer_tag'],
            'targets.*.target_id'    => ['required_with:targets', 'integer'],
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

            // إذا العرض private => لازم targets على الأقل واحد
            if ($this->input('scope') === 'private') {
                $targets = $this->input('targets', []);
                if (empty($targets) || !is_array($targets) || count($targets) < 1) {
                    $v->errors()->add('targets', 'يجب تحديد مستهدف واحد على الأقل لأن العرض خاص (private).');
                }
            }

            // تحقق صارم من الحقول حسب reward_type لكل item
            $items = $this->input('items', []);
            if (!is_array($items)) {
                return;
            }

            foreach ($items as $index => $item) {
                $rewardType = $item['reward_type'] ?? null;

                // تنظيف وجود الحقول المتعارضة سيتم فرضه هنا
                if ($rewardType === 'discount_percent') {
                    if (empty($item['discount_percent'])) {
                        $v->errors()->add("items.$index.discount_percent", 'خصم النسبة مطلوب عند اختيار discount_percent.');
                    }
                    if (!empty($item['discount_fixed'])) {
                        $v->errors()->add("items.$index.discount_fixed", 'لا يمكن إرسال discount_fixed مع discount_percent.');
                    }
                    if (!empty($item['bonus_product_id']) || !empty($item['bonus_qty'])) {
                        $v->errors()->add("items.$index.bonus_qty", 'لا يمكن إرسال bonus fields مع discount_percent.');
                    }
                }

                if ($rewardType === 'discount_fixed') {
                    if (empty($item['discount_fixed'])) {
                        $v->errors()->add("items.$index.discount_fixed", 'خصم المبلغ مطلوب عند اختيار discount_fixed.');
                    }
                    if (!empty($item['discount_percent'])) {
                        $v->errors()->add("items.$index.discount_percent", 'لا يمكن إرسال discount_percent مع discount_fixed.');
                    }
                    if (!empty($item['bonus_product_id']) || !empty($item['bonus_qty'])) {
                        $v->errors()->add("items.$index.bonus_qty", 'لا يمكن إرسال bonus fields مع discount_fixed.');
                    }
                }

                if ($rewardType === 'bonus_qty') {
                    if (empty($item['bonus_product_id'])) {
                        $v->errors()->add("items.$index.bonus_product_id", 'منتج البونص مطلوب عند اختيار bonus_qty.');
                    }
                    if (empty($item['bonus_qty'])) {
                        $v->errors()->add("items.$index.bonus_qty", 'كمية البونص مطلوبة عند اختيار bonus_qty.');
                    }
                    if (!empty($item['discount_percent']) || !empty($item['discount_fixed'])) {
                        $v->errors()->add("items.$index.discount_fixed", 'لا يمكن إرسال حقول الخصم مع bonus_qty.');
                    }
                }
            }

            // تحقق target_id حسب target_type
            $targets = $this->input('targets', []);
            if (!is_array($targets)) {
                return;
            }

            foreach ($targets as $index => $target) {
                $type = $target['target_type'] ?? null;
                $id   = $target['target_id'] ?? null;

                if (!$type || !$id) {
                    continue;
                }

                if ($type === 'customer') {
                    if (!DB::table('users')->where('id', $id)->exists()) {
                        $v->errors()->add("targets.$index.target_id", 'العميل المحدد غير موجود.');
                    }
                }

                if ($type === 'customer_category') {
                    if (!DB::table('categories')->where('id', $id)->exists()) {
                        $v->errors()->add("targets.$index.target_id", 'تصنيف العميل المحدد غير موجود.');
                    }
                }

                if ($type === 'customer_tag') {
                    if (!DB::table('tags')->where('id', $id)->exists()) {
                        $v->errors()->add("targets.$index.target_id", 'وسم العميل المحدد غير موجود.');
                    }
                }
            }
        });
    }

    public function validatedPayload(): array
    {
        return $this->only([
            'title',
            'description',
            'scope',
            'status',
            'start_at',
            'end_at',
        ]);
    }

    public function itemsPayload(): array
    {
        $items = $this->input('items', []) ?? [];
        if (!is_array($items)) {
            return [];
        }

        // نرجّع فقط الحقول التي نحتاجها للـ createMany
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
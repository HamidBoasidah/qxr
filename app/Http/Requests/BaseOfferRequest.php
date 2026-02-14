<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

abstract class BaseOfferRequest extends FormRequest
{
    /**
     * تحقق صارم من الحقول حسب reward_type لكل item
     */
    protected function validateRewardTypes(Validator $v, array $items): void
    {
        foreach ($items as $index => $item) {
            $rewardType = $item['reward_type'] ?? null;

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
    }

    /**
     * تحقق أن كل المنتجات (والبونص) تابعة لنفس الشركة
     */
    protected function validateProductOwnership(Validator $v, array $items): void
    {
        $productIds = [];
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $productIds[] = (int) $item['product_id'];
            }
            if (!empty($item['bonus_product_id'])) {
                $productIds[] = (int) $item['bonus_product_id'];
            }
        }

        $productIds = array_values(array_unique($productIds));

        if (!empty($productIds)) {
            $companyId = Auth::id();

            $ownedCount = Product::query()
                ->where('company_user_id', $companyId)
                ->whereIn('id', $productIds)
                ->count();

            if ($ownedCount !== count($productIds)) {
                $v->errors()->add('items', 'بعض المنتجات المختارة لا تتبع شركتك، يرجى اختيار منتجات شركتك فقط.');
            }
        }
    }

    /**
     * تحقق target_id حسب target_type
     */
    protected function validateTargets(Validator $v, array $targets): void
    {
        foreach ($targets as $index => $target) {
            $type = $target['target_type'] ?? null;
            $id   = $target['target_id'] ?? null;

            if (!$type || !$id) {
                continue;
            }

            if ($type === 'customer') {
                if (!DB::table('users')->where('id', $id)->where('user_type', 'customer')->exists()) {
                    $v->errors()->add("targets.$index.target_id", 'العميل المحدد غير موجود.');
                }
            }

            if ($type === 'customer_category') {
                if (!DB::table('categories')->where('id', $id)->where('category_type', 'customer')->exists()) {
                    $v->errors()->add("targets.$index.target_id", 'تصنيف العميل المحدد غير موجود.');
                }
            }

            if ($type === 'customer_tag') {
                if (!DB::table('tags')->where('id', $id)->where('tag_type', 'customer')->exists()) {
                    $v->errors()->add("targets.$index.target_id", 'وسم العميل المحدد غير موجود.');
                }
            }
        }
    }
}

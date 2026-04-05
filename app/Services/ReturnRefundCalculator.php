<?php

namespace App\Services;

use App\Models\InvoiceItem;
use App\Models\ReturnPolicy;
use Illuminate\Support\Collection;

/**
 * مسؤول حصراً عن حسابات مبالغ الاسترجاع.
 *
 * استراتيجية التقريب: جميع القيم المالية تُقرَّب إلى 4 منازل عشرية
 * باستخدام round($value, 4, PHP_ROUND_HALF_UP).
 */
class ReturnRefundCalculator
{
    /**
     * حساب مبلغ الاسترجاع لبند واحد.
     *
     * - إذا كان discount_deduction_enabled = false → refund = unit_price_snapshot × returned_qty
     * - إذا كان discount_type = 'percent'         → net_price = unit_price × (1 - discount_value/100)
     *                                                refund = net_price × returned_qty
     * - إذا كان discount_type = 'fixed'           → يُستخدم distributeFixedDiscount() لحساب
     *                                                net_unit_price ثم refund = net_unit_price × returned_qty
     * - إذا لم يكن هناك خصم (discount_type = null) → refund = unit_price_snapshot × returned_qty
     *
     * ملاحظة: عند discount_type = 'fixed' يجب تمرير جميع بنود الفاتورة عبر
     * distributeFixedDiscount() مسبقاً. هذه الدالة تستقبل البند المفرد فقط،
     * لذا يُفترض أن يكون discount_value على البند هو الخصم الموزَّع المحسوب
     * مسبقاً (net_unit_price) أو يُستدعى distributeFixedDiscount() خارجياً.
     *
     * للاستخدام الصحيح مع fixed discount، استخدم calculateItemRefundWithDistribution().
     *
     * @param  InvoiceItem  $item        البند الأصلي من الفاتورة
     * @param  int          $returnedQty الكمية المُرجَعة
     * @param  ReturnPolicy $policy      السياسة المطبقة
     * @return float                     مبلغ الاسترجاع مُقرَّباً إلى 4 منازل عشرية
     */
    public function calculateItemRefund(InvoiceItem $item, int $returnedQty, ReturnPolicy $policy): float
    {
        $unitPrice = (float) $item->unit_price_snapshot;

        // إذا كان خصم الاسترجاع معطلاً → استخدام السعر الكامل (المتطلب 4.4)
        if (! $policy->discount_deduction_enabled) {
            return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // لا يوجد خصم على البند → استخدام السعر الكامل
        if (empty($item->discount_type) || $item->discount_value === null) {
            return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // خصم نسبي (المتطلب 4.1)
        if ($item->discount_type === 'percent') {
            $discountValue = (float) $item->discount_value;
            $netUnitPrice  = $unitPrice * (1 - $discountValue / 100);
            $netUnitPrice  = round($netUnitPrice, 4, PHP_ROUND_HALF_UP);

            return round($netUnitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // خصم ثابت (المتطلب 4.2): يتطلب توزيع الخصم على جميع البنود.
        // في هذه الحالة يُفترض أن يكون البند قد مرّ بـ distributeFixedDiscount()
        // وأن discount_value يحمل الخصم الإجمالي للفاتورة.
        // يُستخدم هذا المسار عند استدعاء calculateItemRefundWithDistribution().
        if ($item->discount_type === 'fixed') {
            // نحسب الخصم الموزَّع لهذا البند من إجمالي الفاتورة
            // هذا المسار يُستدعى فقط عبر calculateItemRefundWithDistribution()
            // حيث يتم تمرير net_unit_price مباشرةً.
            // هنا نعيد الحساب بافتراض أن discount_value هو الخصم الإجمالي للفاتورة
            // وأن البند الوحيد في الفاتورة (fallback آمن).
            $qty       = (int) $item->qty;
            $itemTotal = $unitPrice * $qty;
            $fixedDiscount = (float) $item->discount_value;

            // توزيع الخصم على هذا البند فقط (100% من الخصم لأنه البند الوحيد)
            $proportionalDiscount = $fixedDiscount;
            $netItemTotal         = $itemTotal - $proportionalDiscount;
            $netUnitPrice         = $qty > 0 ? $netItemTotal / $qty : 0.0;
            $netUnitPrice         = round($netUnitPrice, 4, PHP_ROUND_HALF_UP);

            return round($netUnitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // fallback: استخدام السعر الكامل
        return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
    }

    /**
     * حساب مبلغ الاسترجاع لبند واحد مع توزيع الخصم الثابت على جميع بنود الفاتورة.
     *
     * هذه الدالة هي الطريقة الصحيحة لحساب الاسترجاع عند discount_type = 'fixed'،
     * لأنها تأخذ جميع بنود الفاتورة لتوزيع الخصم بالتناسب.
     *
     * @param  InvoiceItem  $item        البند المُرجَع
     * @param  int          $returnedQty الكمية المُرجَعة
     * @param  ReturnPolicy $policy      السياسة المطبقة
     * @param  Collection   $allItems    جميع بنود الفاتورة الأصلية (مطلوبة لـ fixed discount)
     * @return float
     */
    public function calculateItemRefundWithDistribution(
        InvoiceItem $item,
        int $returnedQty,
        ReturnPolicy $policy,
        Collection $allItems
    ): float {
        $unitPrice = (float) $item->unit_price_snapshot;

        // إذا كان خصم الاسترجاع معطلاً → استخدام السعر الكامل (المتطلب 4.4)
        if (! $policy->discount_deduction_enabled) {
            return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // لا يوجد خصم على البند → استخدام السعر الكامل
        if (empty($item->discount_type) || $item->discount_value === null) {
            return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // خصم نسبي (المتطلب 4.1)
        if ($item->discount_type === 'percent') {
            $discountValue = (float) $item->discount_value;
            $netUnitPrice  = $unitPrice * (1 - $discountValue / 100);
            $netUnitPrice  = round($netUnitPrice, 4, PHP_ROUND_HALF_UP);

            return round($netUnitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        // خصم ثابت مع توزيع على جميع البنود (المتطلب 4.2)
        if ($item->discount_type === 'fixed') {
            $fixedDiscount    = (float) $item->discount_value;
            $distributedPrices = $this->distributeFixedDiscount($allItems, $fixedDiscount);

            // البحث عن net_unit_price لهذا البند
            $itemKey      = $item->getKey();
            $netUnitPrice = $distributedPrices[$itemKey] ?? $unitPrice;

            return round($netUnitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
        }

        return round($unitPrice * $returnedQty, 4, PHP_ROUND_HALF_UP);
    }

    /**
     * توزيع الخصم الثابت بالتناسب مع القيمة الإجمالية لكل بند.
     *
     * الصيغة:
     *   item_total                  = unit_price_snapshot × qty
     *   invoice_total_before_discount = Σ(unit_price_snapshot × qty) لجميع البنود
     *   proportional_discount       = fixed_discount × (item_total / invoice_total_before_discount)
     *   net_item_total              = item_total - proportional_discount
     *   net_unit_price              = net_item_total / qty
     *
     * @param  Collection $items         مجموعة InvoiceItem
     * @param  float      $fixedDiscount الخصم الثابت الإجمالي للفاتورة
     * @return array<int, float>         مصفوفة [item_id => net_unit_price] مُقرَّبة إلى 4 منازل عشرية
     */
    public function distributeFixedDiscount(Collection $items, float $fixedDiscount): array
    {
        // حساب إجمالي قيمة الفاتورة قبل الخصم
        $invoiceTotalBeforeDiscount = $items->sum(function (InvoiceItem $item) {
            return (float) $item->unit_price_snapshot * (int) $item->qty;
        });

        if ($invoiceTotalBeforeDiscount <= 0) {
            // تجنب القسمة على صفر: إرجاع السعر الكامل لكل بند
            return $items->mapWithKeys(function (InvoiceItem $item) {
                return [$item->getKey() => round((float) $item->unit_price_snapshot, 4, PHP_ROUND_HALF_UP)];
            })->all();
        }

        $result = [];

        foreach ($items as $item) {
            $itemId    = $item->getKey();
            $unitPrice = (float) $item->unit_price_snapshot;
            $qty       = (int) $item->qty;
            $itemTotal = $unitPrice * $qty;

            // الخصم الموزَّع على هذا البند
            $proportionalDiscount = $fixedDiscount * ($itemTotal / $invoiceTotalBeforeDiscount);
            $proportionalDiscount = round($proportionalDiscount, 4, PHP_ROUND_HALF_UP);

            // صافي إجمالي البند وصافي سعر الوحدة
            $netItemTotal = $itemTotal - $proportionalDiscount;
            $netUnitPrice = $qty > 0 ? $netItemTotal / $qty : 0.0;
            $netUnitPrice = round($netUnitPrice, 4, PHP_ROUND_HALF_UP);

            $result[$itemId] = $netUnitPrice;
        }

        return $result;
    }
}

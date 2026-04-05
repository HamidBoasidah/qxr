<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\ReturnInvoice;
use App\Models\ReturnInvoiceItem;
use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReturnInvoiceSeeder extends Seeder
{
    /**
     * إنشاء فواتير استرجاع وهمية.
     *
     * الخطوات:
     * 1. التأكد من وجود سياسات استرجاع لكل شركة لديها فواتير
     * 2. ربط الفواتير غير المرتبطة بسياسة استرجاع
     * 3. تحديث بعض الفواتير إلى حالة paid
     * 4. إنشاء فواتير الاسترجاع
     */
    public function run(): void
    {
        // ── الخطوة 1: ضمان وجود سياسة لكل شركة لديها فواتير ──────────────────
        $companiesWithInvoices = Invoice::with('order')
            ->get()
            ->map(fn($inv) => $inv->order?->company_user_id)
            ->filter()
            ->unique()
            ->values();

        foreach ($companiesWithInvoices as $companyId) {
            $hasPolicy = ReturnPolicy::where('company_id', $companyId)
                ->where('is_active', true)
                ->exists();

            if (! $hasPolicy) {
                ReturnPolicy::create([
                    'company_id'                 => $companyId,
                    'name'                       => 'سياسة الاسترجاع الافتراضية',
                    'return_window_days'         => 30,
                    'max_return_ratio'           => 1.0,
                    'bonus_return_enabled'       => false,
                    'bonus_return_ratio'         => null,
                    'discount_deduction_enabled' => false,
                    'min_days_before_expiry'     => 0,
                    'is_default'                 => true,
                    'is_active'                  => true,
                ]);
            }
        }

        // ── الخطوة 2: ربط الفواتير غير المرتبطة بسياسة ───────────────────────
        $invoicesWithoutPolicy = Invoice::whereNull('return_policy_id')
            ->with('order')
            ->get();

        foreach ($invoicesWithoutPolicy as $invoice) {
            $companyId = $invoice->order?->company_user_id;
            if (! $companyId) continue;

            $policy = ReturnPolicy::where('company_id', $companyId)
                ->where('is_active', true)
                ->first();

            if ($policy) {
                $invoice->update(['return_policy_id' => $policy->id]);
            }
        }

        // ── الخطوة 3: تحديث بعض الفواتير إلى paid ────────────────────────────
        $paidCount = Invoice::where('status', 'paid')
            ->whereHas('items')
            ->whereNotNull('return_policy_id')
            ->count();

        if ($paidCount < 10) {
            Invoice::whereHas('items')
                ->whereNotNull('return_policy_id')
                ->whereIn('status', ['unpaid', 'draft'])
                ->inRandomOrder()
                ->limit(20)
                ->update(['status' => 'paid']);
        }

        // ── الخطوة 4: إنشاء فواتير الاسترجاع ────────────────────────────────
        $paidInvoices = Invoice::where('status', 'paid')
            ->whereHas('items')
            ->whereNotNull('return_policy_id')
            ->whereDoesntHave('returnInvoice')
            ->with(['items', 'order'])
            ->inRandomOrder()
            ->limit(20)
            ->get();

        if ($paidInvoices->isEmpty()) {
            $this->command->warn('ReturnInvoiceSeeder: لا توجد فواتير مدفوعة مناسبة بعد المعالجة.');
            return;
        }

        $statuses = ['pending', 'pending', 'pending', 'approved', 'rejected'];
        $created  = 0;

        foreach ($paidInvoices as $invoice) {
            $policy = ReturnPolicy::where('id', $invoice->return_policy_id)
                ->where('is_active', true)
                ->first();

            if (! $policy) continue;

            $items = $invoice->items;
            if ($items->isEmpty()) continue;

            // استرجاع جزئي أو كامل عشوائياً
            $countToReturn = rand(1, $items->count());
            $itemsToReturn = $items->random($countToReturn);
            $totalRefund   = 0;
            $itemsData     = [];

            foreach ($itemsToReturn as $item) {
                $returnedQty  = rand(1, max(1, (int) $item->qty));
                $unitPrice    = (float) $item->unit_price_snapshot;
                $refundAmount = $unitPrice * $returnedQty;

                if ($policy->discount_deduction_enabled && $item->discount_type === 'percent' && $item->discount_value) {
                    $refundAmount = $unitPrice * (1 - (float) $item->discount_value / 100) * $returnedQty;
                }

                $refundAmount  = round($refundAmount, 4, PHP_ROUND_HALF_UP);
                $totalRefund  += $refundAmount;

                $itemsData[] = [
                    'original_item_id'        => $item->id,
                    'returned_quantity'        => $returnedQty,
                    'unit_price_snapshot'      => $unitPrice,
                    'discount_type_snapshot'   => $item->discount_type,
                    'discount_value_snapshot'  => $item->discount_value,
                    'expiry_date_snapshot'     => $item->expiry_date,
                    'is_bonus'                 => (bool) $item->is_bonus,
                    'refund_amount'            => $refundAmount,
                ];
            }

            if (empty($itemsData)) continue;

            $returnInvoice = ReturnInvoice::create([
                'original_invoice_id' => $invoice->id,
                'company_id'          => $invoice->order->company_user_id,
                'return_policy_id'    => $policy->id,
                'total_refund_amount' => round($totalRefund, 4, PHP_ROUND_HALF_UP),
                'status'              => $statuses[array_rand($statuses)],
                'notes'               => rand(0, 1) ? $this->randomNote() : null,
            ]);

            foreach ($itemsData as $itemData) {
                ReturnInvoiceItem::create(
                    array_merge(['return_invoice_id' => $returnInvoice->id], $itemData)
                );
            }

            $created++;
        }

        $this->command->info("ReturnInvoiceSeeder: تم إنشاء {$created} فاتورة استرجاع.");
    }

    private function randomNote(): string
    {
        $notes = [
            'المنتج تالف عند الاستلام.',
            'المنتج لا يطابق المواصفات المطلوبة.',
            'تم الاسترجاع بناءً على طلب العميل.',
            'المنتج منتهي الصلاحية.',
            'خطأ في الكمية المُسلَّمة.',
            'المنتج لم يُستخدم وتم إرجاعه.',
            'تم الاتفاق مع العميل على الاسترجاع.',
        ];

        return $notes[array_rand($notes)];
    }
}

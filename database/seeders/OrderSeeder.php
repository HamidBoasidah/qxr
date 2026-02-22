<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemBonus;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Address;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $fakerAr = \Faker\Factory::create('ar_SA');

        $arabicNotes = [
            'تم تحديث الحالة بعد مراجعة الطلب.',
            'تم التواصل مع العميل وتأكيد التفاصيل.',
            'تم تجهيز الشحنة وجاهزة للتسليم.',
            'تم إلغاء الطلب بناءً على طلب العميل.',
            'تمت الموافقة على الطلب من قبل الإدارة.',
            'تم تسجيل ملاحظة بخصوص المخزون.',
            'تم تسليم الطلب للعميل بنجاح.',
            'تم رفض الطلب لعدم استيفاء الشروط.',
            'تم إضافة ملاحظة خدمة العملاء.',
            'تم تحديث معلومات الشحن.',
        ];

        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        if (count($companyIds) < 3) {
            $extra = 3 - count($companyIds);
            User::factory($extra)->create(['user_type' => 'company']);
            $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        }

        $customerIds = User::where('user_type', 'customer')->pluck('id')->toArray();
        if (count($customerIds) < 5) {
            $extra = 5 - count($customerIds);
            User::factory($extra)->create(['user_type' => 'customer']);
            $customerIds = User::where('user_type', 'customer')->pluck('id')->toArray();
        }

        // cache offers by company for quick lookup
        $offersByCompany = Offer::all()->groupBy('company_user_id');

        $statusFlows = [
            ['pending', 'approved', 'preparing', 'shipped', 'delivered'],
            ['pending', 'approved', 'preparing', 'cancelled'],
            ['pending', 'approved', 'rejected'],
            ['pending', 'cancelled'],
        ];

        $ordersToCreate = 40;

        for ($i = 0; $i < $ordersToCreate; $i++) {
            $companyId = Arr::random($companyIds);
            $customerId = Arr::random($customerIds);

            $flow = Arr::random($statusFlows);
            $finalStatus = Arr::last($flow);

            $submittedAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));
            $timeCursor = (clone $submittedAt);
            $approvedAt = null;
            $deliveredAt = null;

            // ensure company has products
            $products = Product::where('company_user_id', $companyId)->inRandomOrder()->get();
            if ($products->count() < 3) {
                $toCreate = 3 - $products->count();
                Product::factory($toCreate)->create(['company_user_id' => $companyId]);
                $products = Product::where('company_user_id', $companyId)->inRandomOrder()->get();
            }

            // pick or create a delivery address for this customer
            $deliveryAddressId = Address::where('user_id', $customerId)->inRandomOrder()->value('id')
                ?? Address::factory()->create(['user_id' => $customerId])->id;

            $order = Order::create([
                'order_no' => 'ORD-' . Carbon::now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'company_user_id' => $companyId,
                'customer_user_id' => $customerId,
                'status' => $finalStatus,
                'submitted_at' => $submittedAt,
                'approved_at' => null,
                'approved_by_user_id' => null,
                'delivered_at' => null,
                'delivery_address_id' => $deliveryAddressId,
                'notes_customer' => Arr::random($arabicNotes),
                'notes_company' => Arr::random($arabicNotes),
            ]);

            // create items
            $itemsCount = rand(1, 4);
            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                $qty = rand(1, 8);
                $unitPrice = $product->base_price ?? rand(50, 900) / 10;
                $discount = rand(0, 100) < 35 ? round($unitPrice * (rand(5, 25) / 100), 2) : 0;
                $netPrice = max($unitPrice - $discount, 0);
                $companyOffers = $offersByCompany[$companyId] ?? collect();
                $offerId = $companyOffers->isNotEmpty() ? $companyOffers->random()->id : null;
                $selectedOfferId = ($offerId && rand(0, 100) < 55) ? $offerId : null;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => $discount,
                    'final_line_total_snapshot' => $netPrice * $qty,
                    'selected_offer_id' => $selectedOfferId,
                ]);

                // optional bonuses
                if (rand(0, 100) < 35) {
                    $bonusesCount = rand(1, 2);
                    for ($b = 0; $b < $bonusesCount; $b++) {
                        $bonusProduct = $products->random();
                        OrderItemBonus::create([
                            'order_item_id' => $orderItem->id,
                            'offer_id' => $selectedOfferId,
                            'bonus_product_id' => $bonusProduct->id,
                            'bonus_qty' => rand(1, 3),
                        ]);
                    }
                }
            }

            // status logs timeline
            $previous = null;
            foreach ($flow as $status) {
                $timeCursor->addMinutes(rand(30, 240));

                if ($status === 'approved') {
                    $approvedAt = (clone $timeCursor);
                }
                if ($status === 'delivered') {
                    $deliveredAt = (clone $timeCursor);
                }

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status' => $previous,
                    'to_status' => $status,
                    'changed_by_user_id' => Arr::random([$companyId, $customerId, Arr::random($customerIds)]),
                    'note' => Arr::random($arabicNotes),
                    'changed_at' => (clone $timeCursor),
                ]);

                $previous = $status;
            }

            // update time-based fields
            $order->update([
                'approved_at' => $approvedAt,
                'approved_by_user_id' => $approvedAt ? $companyId : null,
                'delivered_at' => $deliveredAt,
            ]);
        }
    }
}

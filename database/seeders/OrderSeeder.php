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
        $arabicNotes = [
            'تم تحديث الحالة بعد مراجعة الطلب.',
            'تم التواصل مع الصيدلية وتأكيد التفاصيل.',
            'تم تجهيز الشحنة وجاهزة للتسليم.',
            'تم إلغاء الطلب بناءً على طلب الصيدلية.',
            'تمت الموافقة على الطلب من قبل الشركة.',
            'تم تسجيل ملاحظة بخصوص المخزون.',
            'تم تسليم الطلب للصيدلية بنجاح.',
            'تم رفض الطلب لعدم توفر الكمية المطلوبة.',
            'يرجى مراجعة الكميات المطلوبة.',
            'تم تحديث معلومات التوصيل.',
        ];

        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        $customerIds = User::where('user_type', 'customer')->pluck('id')->toArray();

        if (empty($companyIds) || empty($customerIds)) return;

        $offersByCompany = Offer::all()->groupBy('company_user_id');

        $statusFlows = [
            ['pending', 'approved', 'preparing', 'shipped', 'delivered'],
            ['pending', 'approved', 'preparing', 'cancelled'],
            ['pending', 'approved', 'rejected'],
            ['pending', 'cancelled'],
            ['pending'],
        ];

        for ($i = 0; $i < 30; $i++) {
            $companyId = Arr::random($companyIds);
            $customerId = Arr::random($customerIds);

            $flow = Arr::random($statusFlows);
            $finalStatus = Arr::last($flow);

            $submittedAt = Carbon::now()->subDays(rand(0, 60))->subHours(rand(0, 23));
            $timeCursor = (clone $submittedAt);
            $approvedAt = null;
            $deliveredAt = null;

            $products = Product::where('company_user_id', $companyId)->get();
            if ($products->isEmpty()) continue;

            $deliveryAddressId = Address::where('user_id', $customerId)->inRandomOrder()->value('id');
            if (!$deliveryAddressId) {
                $deliveryAddressId = Address::factory()->create(['user_id' => $customerId])->id;
            }

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

            $itemsCount = rand(1, min(4, $products->count()));
            $selectedProducts = $products->random($itemsCount);

            foreach ($selectedProducts as $product) {
                $qty = rand(2, 20);
                $unitPrice = $product->base_price;
                $discount = rand(0, 100) < 30 ? round($unitPrice * (rand(5, 15) / 100), 2) : 0;
                $netPrice = max($unitPrice - $discount, 0);
                $companyOffers = $offersByCompany[$companyId] ?? collect();
                $selectedOfferId = ($companyOffers->isNotEmpty() && rand(0, 100) < 40) ? $companyOffers->random()->id : null;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'unit_price_snapshot' => $unitPrice,
                    'discount_amount_snapshot' => $discount,
                    'final_line_total_snapshot' => $netPrice * $qty,
                    'selected_offer_id' => $selectedOfferId,
                ]);

                if (rand(0, 100) < 25) {
                    OrderItemBonus::create([
                        'order_item_id' => $orderItem->id,
                        'offer_id' => $selectedOfferId,
                        'bonus_product_id' => $product->id,
                        'bonus_qty' => rand(1, 2),
                    ]);
                }
            }

            $previous = null;
            foreach ($flow as $status) {
                $timeCursor->addMinutes(rand(30, 240));

                if ($status === 'approved') $approvedAt = (clone $timeCursor);
                if ($status === 'delivered') $deliveredAt = (clone $timeCursor);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'from_status' => $previous,
                    'to_status' => $status,
                    'changed_by_user_id' => Arr::random([$companyId, $customerId]),
                    'note' => Arr::random($arabicNotes),
                    'changed_at' => (clone $timeCursor),
                ]);

                $previous = $status;
            }

            $order->update([
                'approved_at' => $approvedAt,
                'approved_by_user_id' => $approvedAt ? $companyId : null,
                'delivered_at' => $deliveredAt,
            ]);
        }
    }
}

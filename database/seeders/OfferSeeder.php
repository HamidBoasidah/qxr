<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        if (empty($companyIds)) return;

        $titles = [
            'عرض شراء 3 علب باراسيتامول واحصل على 1 مجاناً',
            'خصم 15% على المضادات الحيوية',
            'عرض الفيتامينات - اشتري 2 واحصل على خصم 20%',
            'تخفيضات على أدوية الضغط والسكري',
            'عرض خاص على مستلزمات طبية',
            'خصم نهاية الشهر على أدوية الأطفال',
            'عرض أدوية الجهاز الهضمي - خصم 10%',
            'عرض ترويجي على قطرات العيون',
            'خصم للصيدليات الجديدة - 25% على أول طلب',
            'عرض الكميات - اشتري كرتون واحصل على خصم خاص',
        ];

        foreach ($companyIds as $companyId) {
            $products = Product::where('company_user_id', $companyId)->get();
            if ($products->isEmpty()) continue;

            $offersCount = rand(2, 4);
            for ($i = 0; $i < $offersCount; $i++) {
                $start = Carbon::today()->addDays(rand(-10, 5));
                $end = (clone $start)->addDays(rand(7, 30));

                $offer = Offer::create([
                    'company_user_id' => $companyId,
                    'scope' => 'public',
                    'status' => ['active', 'active', 'draft'][array_rand([0, 1, 2])],
                    'title' => $titles[array_rand($titles)],
                    'description' => 'عرض خاص من الشركة على مجموعة مختارة من المنتجات الدوائية.',
                    'start_at' => $start->format('Y-m-d'),
                    'end_at' => $end->format('Y-m-d'),
                ]);

                $itemsCount = rand(1, min(3, $products->count()));
                $selectedProducts = $products->random($itemsCount);

                foreach ($selectedProducts as $product) {
                    $rewardTypes = ['discount_percent', 'discount_fixed', 'bonus_qty'];
                    $rtype = $rewardTypes[array_rand($rewardTypes)];

                    $itemData = [
                        'offer_id' => $offer->id,
                        'product_id' => $product->id,
                        'min_qty' => rand(2, 5),
                        'reward_type' => $rtype,
                    ];

                    if ($rtype === 'discount_percent') {
                        $itemData['discount_percent'] = rand(5, 25);
                    } elseif ($rtype === 'discount_fixed') {
                        $itemData['discount_fixed'] = rand(50, 500);
                    } else {
                        $itemData['bonus_product_id'] = $product->id;
                        $itemData['bonus_qty'] = rand(1, 2);
                    }

                    OfferItem::create($itemData);
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are some company users; create a few if none exist
        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        if (empty($companyIds)) {
            for ($c = 1; $c <= 3; $c++) {
                $company = User::create([
                    'first_name' => 'شركة' . $c,
                    'last_name' => 'تجريبية',
                    'email' => "company-offers{$c}@example.test",
                    'phone_number' => '50000000' . $c,
                    'whatsapp_number' => '70000000' . $c,
                    'password' => bcrypt('password'),
                    'user_type' => 'company',
                    'gender' => 'male',
                    'is_active' => true,
                    'locale' => 'ar',
                ]);
                $companyIds[] = $company->id;
            }
        }

        $titles = [
            'عرض خاص محدود',
            'تخفيضات نهاية الأسبوع',
            'خصم على مجموعة مختارة',
            'عرض اليوم فقط',
            'وفر الآن',
            'عرض العيد',
            'تخفيضات حصرية',
            'عرض 2+1',
            'خصم كبير',
            'عرض ترويجي جديد',
        ];

        for ($i = 1; $i <= 20; $i++) {
            $title = $titles[array_rand($titles)] . ' #' . $i;
            $description = 'وصف تجريبي للعرض ' . $i . ' - يقدم خصومات ومزايا للعملاء.';

            // random dates (date-only)
            $start = Carbon::today()->addDays(rand(-10, 5));
            $end = (clone $start)->addDays(rand(1, 30));

            // pick a random company for this offer
            $companyId = $companyIds[array_rand($companyIds)];

            // ensure this company has products; if not create a fallback product for it
            $productIds = Product::where('company_user_id', $companyId)->pluck('id')->toArray();
            if (empty($productIds)) {
                $p = Product::create([
                    'company_user_id' => $companyId,
                    'category_id' => Product::inRandomOrder()->value('category_id') ?: 1,
                    'name' => 'منتج افتراضي للشركة ' . $companyId,
                    'sku' => 'SKU-C' . $companyId . '-1',
                    'description' => 'منتج افتراضي أنشئ تلقائياً',
                    'unit_name' => 'حبة',
                    'base_price' => 10.0,
                    'is_active' => true,
                    'main_image' => null,
                ]);
                $productIds = [$p->id];
            }

            $statuses = ['draft', 'active', 'paused'];

            $offer = Offer::create([
                'company_user_id' => $companyId,
                'scope' => (rand(0, 1) ? 'public' : 'private'),
                'status' => $statuses[array_rand($statuses)],
                'title' => $title,
                'description' => $description,
                'start_at' => $start->format('Y-m-d'),
                'end_at' => $end->format('Y-m-d'),
            ]);

            // create 1-3 items for the offer
            $itemsCount = rand(1, 3);
            $availableProducts = $productIds;
            for ($j = 0; $j < $itemsCount; $j++) {
                if (empty($availableProducts)) break;
                $pid = $availableProducts[array_rand($availableProducts)];

                $rewardTypes = ['discount_percent', 'discount_fixed', 'bonus_qty'];
                $rtype = $rewardTypes[array_rand($rewardTypes)];

                $itemData = [
                    'offer_id' => $offer->id,
                    'product_id' => $pid,
                    'min_qty' => rand(1, 5),
                    'reward_type' => $rtype,
                ];

                if ($rtype === 'discount_percent') {
                    $itemData['discount_percent'] = rand(5, 50);
                    $itemData['discount_fixed'] = null;
                } elseif ($rtype === 'discount_fixed') {
                    $itemData['discount_fixed'] = rand(1, 200) / 10;
                    $itemData['discount_percent'] = null;
                } else {
                    // bonus_qty: choose a different product as bonus if possible
                    $bonusCandidates = array_values(array_diff($availableProducts, [$pid]));
                    $itemData['bonus_product_id'] = $bonusCandidates ? $bonusCandidates[array_rand($bonusCandidates)] : $pid;
                    $itemData['bonus_qty'] = rand(1, 3);
                    $itemData['discount_percent'] = null;
                    $itemData['discount_fixed'] = null;
                }

                OfferItem::create($itemData);
            }
        }
    }
}

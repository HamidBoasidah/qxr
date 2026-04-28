<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use App\Models\User;

class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();

        $ads = [
            // سلايدر الرئيسية
            [
                'title' => 'عروض شركة الجبل للأدوية - خصم 20% على المضادات الحيوية',
                'description' => 'استفد من خصم 20% على جميع المضادات الحيوية من شركة الجبل للصناعات الدوائية. العرض ساري حتى نهاية الشهر.',
                'position' => 'home_slider',
                'sort_order' => 1,
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(25)->format('Y-m-d'),
            ],
            [
                'title' => 'فيتامينات ومكملات غذائية بأسعار مخفضة',
                'description' => 'تشكيلة واسعة من الفيتامينات والمكملات الغذائية بأسعار تنافسية. اطلب الآن واحصل على توصيل مجاني.',
                'position' => 'home_slider',
                'sort_order' => 2,
                'start_date' => now()->subDays(3)->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
            ],
            [
                'title' => 'أدوية الأطفال - اشتري 3 واحصل على 1 مجاناً',
                'description' => 'عرض خاص على أدوية الأطفال. اشتري 3 عبوات من أي منتج واحصل على الرابعة مجاناً.',
                'position' => 'home_slider',
                'sort_order' => 3,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(15)->format('Y-m-d'),
            ],

            // بانر الرئيسية
            [
                'title' => 'سجّل صيدليتك الآن واحصل على خصم 15% على أول طلب',
                'description' => 'انضم لمنصة Quick-XR واستفد من خصم ترحيبي 15% على طلبك الأول.',
                'position' => 'home_banner',
                'sort_order' => 1,
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->addDays(60)->format('Y-m-d'),
            ],
            [
                'title' => 'توصيل سريع لجميع المحافظات اليمنية',
                'description' => 'نوصل طلباتك من الأدوية والمستلزمات الطبية لأي مكان في اليمن خلال 24-48 ساعة.',
                'position' => 'home_banner',
                'sort_order' => 2,
                'start_date' => null,
                'end_date' => null,
            ],

            // بانر الأقسام
            [
                'title' => 'خصم خاص على أدوية الضغط والسكري',
                'description' => 'خصومات تصل إلى 25% على أدوية الأمراض المزمنة.',
                'position' => 'category_banner',
                'sort_order' => 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(20)->format('Y-m-d'),
            ],
            [
                'title' => 'مستلزمات طبية بالجملة - أسعار خاصة للصيدليات',
                'description' => 'قفازات، كمامات، شاش معقم وأكثر بأسعار الجملة.',
                'position' => 'category_banner',
                'sort_order' => 2,
                'start_date' => null,
                'end_date' => null,
            ],

            // نافذة منبثقة
            [
                'title' => 'عرض محدود! خصم 30% على الطلب الأول',
                'description' => 'استخدم كود WELCOME30 واحصل على خصم 30% على طلبك الأول. العرض لفترة محدودة!',
                'position' => 'popup',
                'sort_order' => 1,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(7)->format('Y-m-d'),
            ],
        ];

        foreach ($ads as $ad) {
            Advertisement::create(array_merge($ad, [
                'company_user_id' => !empty($companyIds) ? $companyIds[array_rand($companyIds)] : null,
                'is_active' => true,
                'image_path' => null,
            ]));
        }
    }
}

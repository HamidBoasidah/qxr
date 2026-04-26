<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        // تاقات المنتجات (أدوية)
        $productTags = [
            'يحتاج وصفة طبية',
            'بدون وصفة طبية (OTC)',
            'مستورد',
            'إنتاج محلي يمني',
            'مخزن بارد (2-8°C)',
            'قريب انتهاء الصلاحية',
            'منتج جديد',
            'الأكثر طلباً',
            'متوفر بكميات محدودة',
            'بديل متاح',
            'للاستخدام الخارجي فقط',
            'مناسب للحوامل',
            'غير مناسب للأطفال',
            'مادة مراقبة',
            'عرض ترويجي',
        ];

        foreach ($productTags as $t) {
            \App\Models\Tag::firstOrCreate(
                ['name' => $t],
                [
                    'slug' => Str::slug($t),
                    'tag_type' => 'product',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }

        // تاقات العملاء (صيدليات)
        $customerTags = [
            'عميل VIP',
            'عميل جديد',
            'عميل دائم',
            'متأخر في السداد',
            'صيدلية كبيرة',
            'صيدلية ريفية',
            'مؤسسة حكومية',
            'يحتاج متابعة',
            'ائتمان مفتوح',
            'دفع نقدي فقط',
        ];

        foreach ($customerTags as $t) {
            \App\Models\Tag::firstOrCreate(
                ['name' => $t],
                [
                    'slug' => Str::slug($t),
                    'tag_type' => 'customer',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }

        // تاقات الشركات
        $companyTags = [
            'مصنع محلي',
            'وكيل حصري',
            'موزع معتمد',
            'شريك استراتيجي',
            'مورد دولي',
        ];

        foreach ($companyTags as $t) {
            \App\Models\Tag::firstOrCreate(
                ['name' => $t],
                [
                    'slug' => Str::slug($t),
                    'tag_type' => 'company',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}

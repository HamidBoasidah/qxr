<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $productCats = [
            ['name' => 'مسكنات ومضادات الالتهاب', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><rect x="20" y="8" width="24" height="48" rx="12" stroke="#19a5be" stroke-width="2"/><line x1="32" y1="24" x2="32" y2="40" stroke="#19a5be" stroke-width="3"/><line x1="24" y1="32" x2="40" y2="32" stroke="#19a5be" stroke-width="3"/></svg>'],
            ['name' => 'مضادات حيوية', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><ellipse cx="22" cy="32" rx="14" ry="8" transform="rotate(-45 22 32)" fill="#19a5be" opacity="0.3" stroke="#19a5be" stroke-width="2"/><ellipse cx="42" cy="32" rx="14" ry="8" transform="rotate(-45 42 32)" fill="#084784" opacity="0.3" stroke="#084784" stroke-width="2"/></svg>'],
            ['name' => 'أدوية الجهاز الهضمي', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8c-6 0-10 4-10 10v8c0 4 2 8 6 10s6 6 6 12v8" stroke="#19a5be" stroke-width="2.5" stroke-linecap="round"/><circle cx="32" cy="52" r="4" fill="#19a5be" opacity="0.3"/></svg>'],
            ['name' => 'أدوية القلب والضغط', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 56S8 40 8 24c0-8 6-16 14-16 5 0 8 3 10 6 2-3 5-6 10-6 8 0 14 8 14 16 0 16-24 32-24 32z" fill="#19a5be" opacity="0.2" stroke="#19a5be" stroke-width="2"/><path d="M16 32h8l4-8 4 16 4-12 4 4h8" stroke="#19a5be" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'],
            ['name' => 'أدوية السكري', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8l-8 20h16L32 8z" fill="#19a5be" opacity="0.2" stroke="#19a5be" stroke-width="2"/><rect x="24" y="28" width="16" height="28" rx="4" stroke="#19a5be" stroke-width="2"/><line x1="28" y1="36" x2="36" y2="36" stroke="#19a5be" stroke-width="2"/><line x1="28" y1="42" x2="36" y2="42" stroke="#19a5be" stroke-width="2"/></svg>'],
            ['name' => 'فيتامينات ومكملات غذائية', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="32" r="20" fill="#19a5be" opacity="0.15" stroke="#19a5be" stroke-width="2"/><circle cx="32" cy="32" r="10" fill="#19a5be" opacity="0.3"/></svg>'],
            ['name' => 'أدوية الجهاز التنفسي', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8v20" stroke="#19a5be" stroke-width="2.5"/><path d="M32 28c-8 0-16 6-16 16s4 12 10 12c4 0 6-4 6-8" stroke="#19a5be" stroke-width="2" fill="#19a5be" fill-opacity="0.15"/><path d="M32 28c8 0 16 6 16 16s-4 12-10 12c-4 0-6-4-6-8" stroke="#19a5be" stroke-width="2" fill="#19a5be" fill-opacity="0.15"/></svg>'],
            ['name' => 'مستحضرات العناية بالبشرة', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><rect x="20" y="16" width="24" height="40" rx="6" fill="#19a5be" opacity="0.15" stroke="#19a5be" stroke-width="2"/><rect x="26" y="8" width="12" height="12" rx="3" stroke="#19a5be" stroke-width="2"/><circle cx="32" cy="38" r="6" fill="#19a5be" opacity="0.3" stroke="#19a5be" stroke-width="1.5"/></svg>'],
            ['name' => 'أدوية الأطفال', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><circle cx="32" cy="20" r="10" fill="#19a5be" opacity="0.2" stroke="#19a5be" stroke-width="2"/><path d="M20 40c0-6 5-10 12-10s12 4 12 10v12H20V40z" fill="#19a5be" opacity="0.15" stroke="#19a5be" stroke-width="2"/><circle cx="28" cy="18" r="2" fill="#19a5be"/><circle cx="36" cy="18" r="2" fill="#19a5be"/></svg>'],
            ['name' => 'مستلزمات طبية', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><rect x="12" y="20" width="40" height="32" rx="4" fill="#19a5be" opacity="0.15" stroke="#19a5be" stroke-width="2"/><path d="M24 20V14a8 8 0 0116 0v6" stroke="#19a5be" stroke-width="2"/><line x1="32" y1="30" x2="32" y2="44" stroke="#19a5be" stroke-width="3"/><line x1="25" y1="37" x2="39" y2="37" stroke="#19a5be" stroke-width="3"/></svg>'],
            ['name' => 'أدوية العيون', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M8 32s10-16 24-16 24 16 24 16-10 16-24 16S8 32 8 32z" fill="#19a5be" opacity="0.1" stroke="#19a5be" stroke-width="2"/><circle cx="32" cy="32" r="8" fill="#19a5be" opacity="0.25" stroke="#19a5be" stroke-width="2"/><circle cx="32" cy="32" r="3" fill="#19a5be"/></svg>'],
            ['name' => 'أدوية الأعصاب والنفسية', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none"><path d="M32 8c-10 0-18 8-18 18 0 6 3 12 8 15v15h20V41c5-3 8-9 8-15 0-10-8-18-18-18z" fill="#19a5be" opacity="0.15" stroke="#19a5be" stroke-width="2"/><path d="M26 58h12" stroke="#19a5be" stroke-width="2" stroke-linecap="round"/></svg>'],
        ];

        foreach ($productCats as $c) {
            $dataUri = 'data:image/svg+xml;base64,' . base64_encode($c['icon']);

            \App\Models\Category::firstOrCreate(
                ['name' => $c['name']],
                [
                    'slug' => Str::slug($c['name']),
                    'category_type' => 'product',
                    'icon_path' => $dataUri,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }

        // تصنيفات الشركات
        $companyCats = [
            'شركة تصنيع أدوية',
            'وكيل أدوية مستوردة',
            'موزع أدوية بالجملة',
            'مصنع مستحضرات طبية',
        ];

        foreach ($companyCats as $c) {
            \App\Models\Category::firstOrCreate(
                ['name' => $c],
                [
                    'slug' => Str::slug($c),
                    'category_type' => 'company',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }

        // تصنيفات العملاء
        $customerCats = [
            'صيدلية مركزية',
            'صيدلية فرعية',
            'مستودع أدوية',
            'مستشفى خاص',
            'مستشفى حكومي',
            'مركز صحي',
            'عيادة خاصة',
        ];

        foreach ($customerCats as $c) {
            \App\Models\Category::firstOrCreate(
                ['name' => $c],
                [
                    'slug' => Str::slug($c),
                    'category_type' => 'customer',
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]
            );
        }
    }
}

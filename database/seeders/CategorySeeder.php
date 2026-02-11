<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            'مواد غذائية',
            'ملابس',
            'أجهزة إلكترونية',
            'مستلزمات منزلية',
            'أدوات كتابية',
            'مستحضرات تجميل',
            'ألعاب الأطفال',
            'أدوات مهنية',
        ];

        foreach ($cats as $c) {
            \App\Models\Category::firstOrCreate([
                'name' => $c,
            ], [
                'slug' => Str::slug($c),
                'category_type' => 'product',
                'created_by' => null,
                'updated_by' => null,
            ]);
        }
    }
}

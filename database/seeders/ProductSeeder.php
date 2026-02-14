<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there are some product categories (create Arabic defaults if none)
        if (Category::where('category_type', 'product')->count() === 0) {
            $cats = ['مواد غذائية', 'ملابس', 'أجهزة إلكترونية', 'مستلزمات منزلية', 'أدوات كتابية'];
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

        // Ensure there are some company users; create a few if none exist
        $companyIds = User::where('user_type', 'company')->pluck('id')->toArray();
        if (empty($companyIds)) {
            for ($c = 1; $c <= 3; $c++) {
                $company = User::create([
                    'first_name' => 'شركة' . $c,
                    'last_name' => 'تجريبية',
                    'email' => "company{$c}@example.test",
                    'phone_number' => '50000000' . $c,
                    'whatsapp_number' => '70000000' . $c,
                    'password' => Hash::make('password'),
                    'user_type' => 'company',
                    'gender' => 'male',
                    'is_active' => true,
                    'locale' => 'ar',
                ]);
                $companyIds[] = $company->id;
            }
        }

        $units = ['حبة', 'صندوق', 'عبوة', 'زجاجة', 'كرتون'];
        $categoryIds = Category::where('category_type', 'product')->pluck('id')->toArray();

        for ($i = 1; $i <= 50; $i++) {
            $name = 'منتج تجريبي ' . $i;
            // assign to a random company
            $companyId = $companyIds[array_rand($companyIds)];

            Product::create([
                'company_user_id' => $companyId,
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'name' => $name,
                'sku' => 'SKU-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'description' => 'وصف تجريبي للمنتج رقم ' . $i,
                'unit_name' => $units[array_rand($units)],
                'base_price' => rand(100, 10000) / 10,
                'is_active' => true,
                'main_image' => null,
            ]);
        }
    }
}

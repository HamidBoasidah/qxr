<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'خصم',
            'جديد',
            'شائع',
            'موسمي',
            'محدود',
        ];

        foreach ($tags as $t) {
            \App\Models\Tag::firstOrCreate([
                'name' => $t,
            ], [
                'slug' => Str::slug($t),
                'tag_type' => 'product',
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
            ]);
        }
    }
}

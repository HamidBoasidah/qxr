<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Address;
use App\Models\Governorate;
use App\Models\District;
use App\Models\Area;

class FillUserAddressesSeeder extends Seeder
{
    public function run(): void
    {
        $labels = ['الصيدلية', 'المستودع', 'الفرع الرئيسي', 'المكتب'];
        $addresses = [
            'شارع الزبيري، صنعاء',
            'شارع تعز، صنعاء',
            'حي حدة، صنعاء',
            'شارع كريتر، عدن',
            'شارع المعلا، عدن',
            'شارع جمال، تعز',
            'شارع الكورنيش، الحديدة',
            'شارع الثلاثين، إب',
            'شارع الجمهورية، ذمار',
            'شارع مأرب الرئيسي، مأرب',
        ];

        User::withCount('addresses')
            ->chunk(100, function ($users) use ($labels, $addresses) {
                foreach ($users as $user) {
                    if ($user->addresses_count > 0) continue;

                    $gov = Governorate::inRandomOrder()->first();
                    $district = $gov ? District::where('governorate_id', $gov->id)->inRandomOrder()->first() : null;
                    $area = $district ? Area::where('district_id', $district->id)->inRandomOrder()->first() : null;

                    Address::create([
                        'user_id' => $user->id,
                        'label' => $labels[array_rand($labels)],
                        'address' => $addresses[array_rand($addresses)],
                        'governorate_id' => $gov?->id,
                        'district_id' => $district?->id,
                        'area_id' => $area?->id,
                        'lat' => 15.3694 + (rand(-100, 100) / 1000),
                        'lang' => 44.1910 + (rand(-100, 100) / 1000),
                        'is_default' => true,
                        'is_active' => true,
                        'created_by' => null,
                        'updated_by' => null,
                    ]);
                }
            });
    }
}

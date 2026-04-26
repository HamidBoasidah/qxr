<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // شركات أدوية يمنية حقيقية
        $companies = [
            [
                'first_name' => 'شركة الجبل',
                'last_name' => 'للصناعات الدوائية',
                'email' => 'aljabal@company.test',
                'phone_number' => '771000001',
                'whatsapp_number' => '771000001',
                'company_name' => 'شركة الجبل للصناعات الدوائية',
            ],
            [
                'first_name' => 'شركة يدكو',
                'last_name' => 'للأدوية',
                'email' => 'yedco@company.test',
                'phone_number' => '771000002',
                'whatsapp_number' => '771000002',
                'company_name' => 'الشركة اليمنية للصناعات الدوائية (يدكو)',
            ],
            [
                'first_name' => 'شركة شفاكو',
                'last_name' => 'للأدوية',
                'email' => 'shifaco@company.test',
                'phone_number' => '771000003',
                'whatsapp_number' => '771000003',
                'company_name' => 'شركة شفاكو للصناعات الدوائية',
            ],
            [
                'first_name' => 'شركة فارماسي',
                'last_name' => 'اليمن',
                'email' => 'pharmacy-ye@company.test',
                'phone_number' => '771000004',
                'whatsapp_number' => '771000004',
                'company_name' => 'شركة فارماسي اليمن للأدوية',
            ],
            [
                'first_name' => 'مجموعة هائل',
                'last_name' => 'سعيد أنعم',
                'email' => 'hsa-pharma@company.test',
                'phone_number' => '771000005',
                'whatsapp_number' => '771000005',
                'company_name' => 'مجموعة هائل سعيد أنعم - قسم الأدوية',
            ],
            [
                'first_name' => 'شركة سبأفارم',
                'last_name' => 'للأدوية',
                'email' => 'sabapharm@company.test',
                'phone_number' => '771000006',
                'whatsapp_number' => '771000006',
                'company_name' => 'شركة سبأفارم للصناعات الدوائية',
            ],
        ];

        $companyCategory = Category::where('category_type', 'company')->first();

        foreach ($companies as $data) {
            $companyName = $data['company_name'];
            unset($data['company_name']);

            $user = User::create(array_merge($data, [
                'password' => Hash::make('password'),
                'user_type' => 'company',
                'gender' => 'male',
                'is_active' => true,
                'locale' => 'ar',
            ]));

            $user->companyProfile()->create([
                'company_name' => $companyName,
                'category_id' => $companyCategory?->id,
                'is_active' => true,
            ]);
        }

        // صيدليات يمنية (عملاء)
        $pharmacies = [
            ['name' => 'صيدلية الحكمة', 'city' => 'صنعاء', 'email' => 'alhikma@pharmacy.test', 'phone' => '773000001'],
            ['name' => 'صيدلية الشفاء', 'city' => 'صنعاء', 'email' => 'alshifa@pharmacy.test', 'phone' => '773000002'],
            ['name' => 'صيدلية النور', 'city' => 'عدن', 'email' => 'alnoor@pharmacy.test', 'phone' => '773000003'],
            ['name' => 'صيدلية الأمل', 'city' => 'تعز', 'email' => 'alamal@pharmacy.test', 'phone' => '773000004'],
            ['name' => 'صيدلية الرازي', 'city' => 'صنعاء', 'email' => 'alrazi@pharmacy.test', 'phone' => '773000005'],
            ['name' => 'صيدلية ابن سينا', 'city' => 'عدن', 'email' => 'ibnsina@pharmacy.test', 'phone' => '773000006'],
            ['name' => 'صيدلية الإيمان', 'city' => 'الحديدة', 'email' => 'aliman@pharmacy.test', 'phone' => '773000007'],
            ['name' => 'صيدلية السلام', 'city' => 'إب', 'email' => 'alsalam@pharmacy.test', 'phone' => '773000008'],
            ['name' => 'صيدلية المدينة', 'city' => 'ذمار', 'email' => 'almadina@pharmacy.test', 'phone' => '773000009'],
            ['name' => 'صيدلية البركة', 'city' => 'تعز', 'email' => 'albaraka@pharmacy.test', 'phone' => '773000010'],
            ['name' => 'صيدلية الوفاء', 'city' => 'صنعاء', 'email' => 'alwafa@pharmacy.test', 'phone' => '773000011'],
            ['name' => 'صيدلية الصحة', 'city' => 'عدن', 'email' => 'alsiha@pharmacy.test', 'phone' => '773000012'],
            ['name' => 'صيدلية الجزيرة', 'city' => 'الحديدة', 'email' => 'aljazeera@pharmacy.test', 'phone' => '773000013'],
            ['name' => 'صيدلية الريان', 'city' => 'مأرب', 'email' => 'alrayan@pharmacy.test', 'phone' => '773000014'],
            ['name' => 'صيدلية الفردوس', 'city' => 'إب', 'email' => 'alfirdaws@pharmacy.test', 'phone' => '773000015'],
        ];

        $customerCategory = Category::where('category_type', 'customer')->first();

        foreach ($pharmacies as $ph) {
            $user = User::create([
                'first_name' => $ph['name'],
                'last_name' => '- ' . $ph['city'],
                'email' => $ph['email'],
                'phone_number' => $ph['phone'],
                'whatsapp_number' => $ph['phone'],
                'password' => Hash::make('password'),
                'user_type' => 'customer',
                'gender' => 'male',
                'is_active' => true,
                'locale' => 'ar',
            ]);

            $user->customerProfile()->create([
                'business_name' => $ph['name'] . ' - ' . $ph['city'],
                'category_id' => $customerCategory?->id,
                'is_active' => true,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ReturnPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReturnPolicySeeder extends Seeder
{
    /**
     * إنشاء سياسات استرجاع وهمية لكل شركة موجودة.
     * كل شركة تحصل على سياسة افتراضية واحدة + 1-2 سياسات إضافية.
     */
    public function run(): void
    {
        $companies = User::where('user_type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('ReturnPolicySeeder: لا توجد شركات. يُرجى تشغيل UserSeeder أولاً.');
            return;
        }

        $policyNames = [
            'سياسة الاسترجاع القياسية',
            'سياسة الاسترجاع المرنة',
            'سياسة الاسترجاع السريع',
            'سياسة الاسترجاع الموسمية',
            'سياسة الاسترجاع الخاصة',
            'سياسة الاسترجاع المحدودة',
            'سياسة الاسترجاع الممتدة',
        ];

        foreach ($companies as $company) {
            // السياسة الافتراضية لكل شركة
            ReturnPolicy::create([
                'company_id'                 => $company->id,
                'name'                       => 'سياسة الاسترجاع الافتراضية',
                'return_window_days'         => 30,
                'max_return_ratio'           => 1.0,
                'bonus_return_enabled'       => false,
                'bonus_return_ratio'         => null,
                'discount_deduction_enabled' => true,
                'min_days_before_expiry'     => 0,
                'is_default'                 => true,
                'is_active'                  => true,
            ]);

            // سياسة إضافية بخصائص مختلفة
            $extraCount = rand(1, 2);
            for ($i = 0; $i < $extraCount; $i++) {
                $bonusEnabled = (bool) rand(0, 1);
                ReturnPolicy::create([
                    'company_id'                 => $company->id,
                    'name'                       => $policyNames[array_rand($policyNames)] . ' ' . ($i + 2),
                    'return_window_days'         => rand(7, 60),
                    'max_return_ratio'           => round(rand(25, 100) / 100, 2),
                    'bonus_return_enabled'       => $bonusEnabled,
                    'bonus_return_ratio'         => $bonusEnabled ? round(rand(20, 80) / 100, 2) : null,
                    'discount_deduction_enabled' => (bool) rand(0, 1),
                    'min_days_before_expiry'     => rand(0, 1) ? 0 : rand(3, 14),
                    'is_default'                 => false,
                    'is_active'                  => (bool) rand(0, 1),
                ]);
            }
        }

        $total = ReturnPolicy::count();
        $this->command->info("ReturnPolicySeeder: تم إنشاء {$total} سياسة استرجاع.");
    }
}

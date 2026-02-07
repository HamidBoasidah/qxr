<?php

namespace App\Services;

use App\Models\User;
use App\Models\CompanyProfile;
use App\Models\CustomerProfile;
use App\Repositories\UserRepository;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    protected UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function all(array $with = null)
    {
        // $with = null يعني: استخدم defaultWith من الـ Repository (سنفعله في UserRepository)
        return $this->users->all($with);
    }

    public function paginate(int $perPage = 15, array $with = null)
    {
        return $this->users->paginate($perPage, $with);
    }

    public function find($id, array $with = null)
    {
        return $this->users->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return DB::transaction(function () use ($attributes) {

            // 1) فصل بيانات المستخدم عن بيانات البروفايل
            [$userData, $profileData] = $this->splitUserAndProfileData($attributes);

            // 2) إنشاء المستخدم (رفع avatar يتم تلقائياً داخل BaseRepository)
            /** @var User $user */
            $user = $this->users->create($userData);

            // 3) إنشاء/تحديث البروفايل المناسب حسب النوع
            $this->saveProfileForUser($user, $profileData);

            return $user;
        });
    }

    public function update($id, array $attributes)
    {
        return DB::transaction(function () use ($id, $attributes) {

            // لا تقم بتحديث كلمة المرور إذا لم يتم إرسالها
            if (array_key_exists('password', $attributes) && empty($attributes['password'])) {
                unset($attributes['password']);
            }

            [$userData, $profileData] = $this->splitUserAndProfileData($attributes);

            /** @var User $user */
            $user = $this->users->update($id, $userData);

            // تحديث/إنشاء البروفايل المناسب
            $this->saveProfileForUser($user, $profileData);

            return $user;
        });
    }

    public function delete($id)
    {
        return $this->users->delete($id);
    }

    public function activate($id)
    {
        return $this->users->activate($id);
    }

    public function deactivate($id)
    {
        return $this->users->deactivate($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * يفصل بيانات users عن بيانات البروفايل القادمة من لوحة الإدارة
     * حتى لا تختلط الحقول.
     */
    private function splitUserAndProfileData(array $attributes): array
    {
        $profileKeys = [
            // customer
            'business_name',
            'customer_category_id',
            'customer_main_address_id',
            'customer_is_active',

            // company
            'company_name',
            'company_category_id',
            'company_main_address_id',
            'company_is_active',
            'logo', // ملف شعار الشركة (سيتحول إلى logo_path)
        ];

        $profileData = [];
        foreach ($profileKeys as $k) {
            if (array_key_exists($k, $attributes)) {
                $profileData[$k] = $attributes[$k];
                unset($attributes[$k]);
            }
        }

        return [$attributes, $profileData];
    }

    /**
     * يحفظ بروفايل واحد فقط حسب user_type:
     * - customer => customer_profiles
     * - company  => company_profiles
     *
     * ملاحظة: لو تغيّر user_type أثناء التعديل، سيحذف البروفايل القديم ويُنشئ الجديد.
     */
    private function saveProfileForUser(User $user, array $profileData): void
    {
        $user->refresh(); // لضمان أحدث user_type

        if (($user->user_type ?? 'customer') === 'company') {
            // لو كان لديه بروفايل عميل قديم احذفه
            if ($user->customerProfile) {
                $user->customerProfile->delete();
            }

            $this->upsertCompanyProfile($user, $profileData);
            return;
        }

        // customer
        if ($user->companyProfile) {
            // حذف بروفايل الشركة القديم + حذف شعارها من التخزين إن وجد
            $this->deleteCompanyLogoIfExists($user->companyProfile);
            $user->companyProfile->delete();
        }

        $this->upsertCustomerProfile($user, $profileData);
    }

    private function upsertCustomerProfile(User $user, array $profileData): void
    {
        $payload = [
            'business_name'   => $profileData['business_name'] ?? null,
            'category_id'     => $profileData['customer_category_id'] ?? null,
            'main_address_id' => $profileData['customer_main_address_id'] ?? null,
            'is_active'       => array_key_exists('customer_is_active', $profileData)
                ? (bool) $profileData['customer_is_active']
                : true,
        ];

        // إذا كان موجود تحديث، وإلا إنشاء
        CustomerProfile::updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );
    }

    private function upsertCompanyProfile(User $user, array $profileData): void
    {
        $companyProfile = $user->companyProfile ?: CompanyProfile::where('user_id', $user->id)->first();

        $payload = [
            'company_name'   => $profileData['company_name'] ?? null,
            'category_id'    => $profileData['company_category_id'] ?? null,
            'main_address_id'=> $profileData['company_main_address_id'] ?? null,
            'is_active'      => array_key_exists('company_is_active', $profileData)
                ? (bool) $profileData['company_is_active']
                : true,
        ];

        // شعار الشركة (logo -> logo_path)
        if (isset($profileData['logo']) && $profileData['logo'] instanceof UploadedFile) {
            if ($companyProfile) {
                $this->deleteCompanyLogoIfExists($companyProfile);
            }
            $payload['logo_path'] = $this->storeCompanyLogo($profileData['logo']);
        }

        CompanyProfile::updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );
    }

    private function storeCompanyLogo(UploadedFile $file): string
    {
        // نفس فكرة BaseRepository: uuid + مجلد باسم الجدول
        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs('company_profiles', $filename, 'public');
    }

    private function deleteCompanyLogoIfExists(CompanyProfile $profile): void
    {
        if (!$profile->logo_path) {
            return;
        }

        if (Storage::disk('public')->exists($profile->logo_path)) {
            Storage::disk('public')->delete($profile->logo_path);
        }
    }
}
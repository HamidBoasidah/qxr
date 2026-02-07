<?php

namespace App\DTOs;

use App\Models\User;

class UserDTO extends BaseDTO
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $address;
    public $phone_number;
    public $whatsapp_number;
    public $facebook;
    public $x_url;
    public $linkedin;
    public $instagram;
    public $is_active;
    public $locale;
    public $avatar;

    // ✅ جديد
    public $user_type;
    public $gender;

    // ✅ جديد: بيانات البروفايلين (للوحة الإدارة)
    public $customer_profile;
    public $company_profile;

    public $created_by;
    public $updated_by;
    public $created_at;
    public $deleted_at;

    public function __construct(
        $id,
        $first_name,
        $last_name,
        $email,
        $address,
        $phone_number,
        $whatsapp_number,
        $facebook,
        $x_url,
        $linkedin,
        $instagram,
        $is_active,
        $locale,
        $avatar,

        $user_type = 'customer',
        $gender = null,
        $customer_profile = null,
        $company_profile = null,

        $created_by = null,
        $updated_by = null,
        $created_at = null,
        $deleted_at = null
    ) {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->address = $address;
        $this->phone_number = $phone_number;
        $this->whatsapp_number = $whatsapp_number;
        $this->facebook = $facebook;
        $this->x_url = $x_url;
        $this->linkedin = $linkedin;
        $this->instagram = $instagram;
        $this->is_active = $is_active;
        $this->locale = $locale;
        $this->avatar = $avatar;

        $this->user_type = $user_type;
        $this->gender = $gender;
        $this->customer_profile = $customer_profile;
        $this->company_profile = $company_profile;

        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(User $user): self
    {
        // ملاحظة: هذا قد يعمل lazy-load، وهو مقبول حاليًا للوحة الإدارة.
        // لاحقًا سنعمل eager-load في controller لتفادي N+1.
        $customerProfile = $user->customerProfile;
        $companyProfile  = $user->companyProfile;

        $customerCategory = $customerProfile?->category;
        $companyCategory  = $companyProfile?->category;

        return new self(
            $user->id,
            $user->first_name ?? null,
            $user->last_name ?? null,
            $user->email ?? null,
            $user->address ?? null,
            $user->phone_number ?? null,
            $user->whatsapp_number ?? null,
            $user->facebook ?? null,
            $user->x_url ?? null,
            $user->linkedin ?? null,
            $user->instagram ?? null,
            (bool) ($user->is_active ?? false),
            $user->locale ?? null,
            $user->avatar ?? null,

            // ✅ جديد
            $user->user_type ?? 'customer',
            $user->gender ?? null,

            // ✅ بروفايلات
            $customerProfile ? [
                'id' => $customerProfile->id,
                'business_name' => $customerProfile->business_name,
                'category_id' => $customerProfile->category_id,
                'category_name' => $customerCategory?->name,
                'category' => $customerCategory ? [
                    'id' => $customerCategory->id,
                    'name' => $customerCategory->name,
                    'name_ar' => $customerCategory->name_ar ?? null,
                    'name_en' => $customerCategory->name_en ?? null,
                ] : null,
                'main_address_id' => $customerProfile->main_address_id,
                'is_active' => (bool) $customerProfile->is_active,
            ] : null,

            $companyProfile ? [
                'id' => $companyProfile->id,
                'company_name' => $companyProfile->company_name,
                'category_id' => $companyProfile->category_id,
                'category_name' => $companyCategory?->name,
                'category' => $companyCategory ? [
                    'id' => $companyCategory->id,
                    'name' => $companyCategory->name,
                    'name_ar' => $companyCategory->name_ar ?? null,
                    'name_en' => $companyCategory->name_en ?? null,
                ] : null,
                'logo_path' => $companyProfile->logo_path,
                'main_address_id' => $companyProfile->main_address_id,
                'is_active' => (bool) $companyProfile->is_active,
            ] : null,

            $user->created_by ?? null,
            $user->updated_by ?? null,
            $user->created_at?->toDateTimeString() ?? null,
            $user->deleted_at?->toDateTimeString() ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'whatsapp_number' => $this->whatsapp_number,
            'facebook' => $this->facebook,
            'x_url' => $this->x_url,
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'is_active' => $this->is_active,
            'locale' => $this->locale,
            'avatar' => $this->avatar,

            // ✅ جديد
            'user_type' => $this->user_type,
            'gender' => $this->gender,

            // ✅ جديد
            'customer_profile' => $this->customer_profile,
            'company_profile' => $this->company_profile,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,

            // ✅ مفيد مستقبلاً لعرض النوع في جدول الإدارة
            'user_type' => $this->user_type,
        ];
    }
}
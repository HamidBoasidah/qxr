<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // في روت التحديث عندك غالباً model binding: /users/{user}
        $userId = $this->route('user')?->id ?? $this->route('user');

        $baseRules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],

            // ✅ unique مع استثناء المستخدم الحالي
            'email'      => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],

            // ✅ كلمة المرور اختيارية في التحديث
            'password'   => ['nullable', 'string', 'min:8'],

            'avatar'     => ['nullable', 'image', 'max:2048'],

            'phone_number'    => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],

            'user_type' => ['required', 'in:customer,company'],
            'gender'    => ['nullable', 'in:male,female'],

            'facebook'  => ['nullable', 'string', 'max:255'],
            'x_url'     => ['nullable', 'string', 'max:255'],
            'linkedin'  => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],

            'is_active' => ['nullable', 'boolean'],
            'locale'    => ['nullable', 'string', 'max:10'],
        ];

        // ✅ حقول بروفايل العميل
        $customerProfileRules = [
            // أضفنا "nullable" حتى لا يسبب التحقق خطأً عندما لا تُرسل حقول البروفايل المخصصة لنوع المستخدم الآخر
            'business_name'        => ['nullable', 'required_if:user_type,customer', 'string', 'max:255'],
            'customer_category_id' => ['nullable', 'required_if:user_type,customer', 'integer', 'exists:categories,id'],
            'customer_main_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'customer_is_active'   => ['nullable', 'boolean'],
        ];

        // ✅ حقول بروفايل الشركة
        $companyProfileRules = [
            // أضفنا "nullable" لنفس السبب أعلاه
            'company_name'         => ['nullable', 'required_if:user_type,company', 'string', 'max:255'],
            'company_category_id'  => ['nullable', 'required_if:user_type,company', 'integer', 'exists:categories,id'],
            'logo'                 => ['nullable', 'image', 'max:2048'],
            'company_main_address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'company_is_active'    => ['nullable', 'boolean'],
        ];

        return array_merge($baseRules, $customerProfileRules, $companyProfileRules);
    }
}
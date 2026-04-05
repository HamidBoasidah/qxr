<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                       => ['sometimes', 'string', 'max:255'],
            'return_window_days'         => ['sometimes', 'integer', 'min:1'],
            'max_return_ratio'           => ['sometimes', 'numeric', 'between:0.01,1.00'],
            'bonus_return_enabled'       => ['sometimes', 'boolean'],
            'bonus_return_ratio'         => ['required_if:bonus_return_enabled,true', 'nullable', 'numeric', 'between:0.00,1.00'],
            'discount_deduction_enabled' => ['sometimes', 'boolean'],
            'min_days_before_expiry'     => ['sometimes', 'integer', 'min:0'],
            'is_default'                 => ['sometimes', 'boolean'],
            'is_active'                  => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'return_window_days.integer'             => 'عدد أيام نافذة الاسترجاع يجب أن يكون عدداً صحيحاً.',
            'return_window_days.min'                 => 'عدد أيام نافذة الاسترجاع يجب أن يكون عدداً موجباً (1 على الأقل).',
            'max_return_ratio.numeric'               => 'الحد الأقصى لنسبة الاسترجاع يجب أن يكون رقماً عشرياً.',
            'max_return_ratio.between'               => 'الحد الأقصى لنسبة الاسترجاع يجب أن يكون بين 0.01 و 1.00.',
            'bonus_return_ratio.required_if'         => 'نسبة استرجاع البونص مطلوبة عند تفعيل خيار البونص.',
            'bonus_return_ratio.numeric'             => 'نسبة استرجاع البونص يجب أن تكون رقماً عشرياً.',
            'bonus_return_ratio.between'             => 'نسبة استرجاع البونص يجب أن تكون بين 0.00 و 1.00.',
            'min_days_before_expiry.integer'         => 'الحد الأدنى للأيام قبل انتهاء الصلاحية يجب أن يكون عدداً صحيحاً.',
            'min_days_before_expiry.min'             => 'الحد الأدنى للأيام قبل انتهاء الصلاحية يجب أن يكون صفراً أو أكثر.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('bonus_return_enabled')) {
            $this->merge(['bonus_return_enabled' => (bool) $this->input('bonus_return_enabled')]);
        }
        if ($this->has('discount_deduction_enabled')) {
            $this->merge(['discount_deduction_enabled' => (bool) $this->input('discount_deduction_enabled')]);
        }
        if ($this->has('is_default')) {
            $this->merge(['is_default' => (bool) $this->input('is_default')]);
        }
        if ($this->has('is_active')) {
            $this->merge(['is_active' => (bool) $this->input('is_active')]);
        }
    }

    public function validatedPayload(): array
    {
        $fields = [
            'name',
            'return_window_days',
            'max_return_ratio',
            'bonus_return_enabled',
            'bonus_return_ratio',
            'discount_deduction_enabled',
            'min_days_before_expiry',
            'is_default',
            'is_active',
        ];

        return array_filter(
            $this->only($fields),
            fn($value, $key) => $this->has($key),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

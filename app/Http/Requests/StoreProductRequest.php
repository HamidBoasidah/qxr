<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // لاحقًا تقدر تربطها بسياسات (Policy) أو صلاحيات
        return true;
    }

    public function rules(): array
    {
        return [
            // الشركة نأخذها من auth()->id() داخل السيرفس (لا تُرسل من العميل)
            // 'company_user_id' => 'prohibited',

            'category_id'  => ['required', 'integer', 'exists:categories,id'],

            'name'         => ['required', 'string', 'max:255'],
            'sku'          => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],

            'unit_name'    => ['required', 'string', 'max:255'],
            'base_price'   => ['nullable', 'numeric', 'min:0'],

            'is_active'    => ['sometimes', 'boolean'],
            'main_image'   => ['nullable', 'string', 'max:255'],

            // tags (pivot product_tag)
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['integer', 'distinct', 'exists:tags,id'],

            // product_images (مسارات)
            'images'                 => ['nullable', 'array'],
            'images.*.path'          => ['required_with:images', 'string', 'max:255'],
            'images.*.sort_order'    => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'القسم مطلوب.',
            'category_id.exists'   => 'القسم غير موجود.',

            'name.required'        => 'اسم المنتج مطلوب.',
            'unit_name.required'   => 'وحدة البيع مطلوبة.',

            'tag_ids.array'        => 'الوسوم يجب أن تكون مصفوفة.',
            'tag_ids.*.exists'     => 'أحد الوسوم غير موجود.',

            'images.array'         => 'الصور يجب أن تكون مصفوفة.',
            'images.*.path.required_with' => 'مسار الصورة مطلوب.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // ضبط قيم افتراضية
        $this->merge([
            'is_active'  => $this->has('is_active') ? (bool) $this->input('is_active') : true,
            'base_price' => $this->input('base_price', 0),
        ]);
    }

    public function validatedPayload(): array
    {
        // Payload نظيف للسيرفس
        return $this->only([
            'category_id',
            'name',
            'sku',
            'description',
            'unit_name',
            'base_price',
            'is_active',
            'main_image',
        ]);
    }

    public function tagIds(): array
    {
        return $this->input('tag_ids', []) ?? [];
    }

    public function imagesPayload(): array
    {
        return $this->input('images', []) ?? [];
    }
}
<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['required', 'integer', 'exists:categories,id'],

            'name'         => ['required', 'string', 'max:255'],
            'sku'          => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],

            'unit_name'    => ['required', 'string', 'max:255'],
            'base_price'   => ['nullable', 'numeric', 'min:0'],

            'is_active'    => ['sometimes', 'boolean'],
            'main_image'   => ['nullable', 'image', 'max:2048'],

            // tags (pivot product_tag)
            'tag_ids'      => ['nullable', 'array'],
            'tag_ids.*'    => ['integer', 'distinct', 'exists:tags,id'],

            // product_images
            'images'                 => ['nullable', 'array'],
            'images.*'               => ['file', 'image', 'max:2048'],
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->has('is_active') ? (bool) $this->input('is_active') : true,
            'base_price' => $this->input('base_price', 0),
        ]);
    }

    public function validatedPayload(): array
    {
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
        return $this->file('images', []) ?? [];
    }
}

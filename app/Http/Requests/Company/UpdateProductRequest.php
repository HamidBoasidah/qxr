<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'  => ['sometimes', 'integer', 'exists:categories,id'],

            'name'         => ['sometimes', 'string', 'max:255'],
            'sku'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'  => ['sometimes', 'nullable', 'string'],

            'unit_name'    => ['sometimes', 'string', 'max:255'],
            'base_price'   => ['sometimes', 'numeric', 'min:0'],

            'is_active'    => ['sometimes', 'boolean'],
            'main_image'   => ['sometimes', 'nullable', 'image', 'max:2048'],

            // tags (pivot product_tag)
            'tag_ids'      => ['sometimes', 'array'],
            'tag_ids.*'    => ['integer', 'distinct', 'exists:tags,id'],

            // product_images
            'images'                 => ['sometimes', 'array'],
            'images.*'               => ['file', 'image', 'max:2048'],
            'delete_image_ids'       => ['sometimes', 'array'],
            'delete_image_ids.*'     => ['integer', 'distinct', 'exists:product_images,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'القسم غير موجود.',

            'tag_ids.array'      => 'الوسوم يجب أن تكون مصفوفة.',
            'tag_ids.*.exists'   => 'أحد الوسوم غير موجود.',

            'images.array'       => 'الصور يجب أن تكون مصفوفة.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => (bool) $this->input('is_active')]);
        }
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

    public function tagIdsOrNull(): ?array
    {
        return $this->has('tag_ids') ? ($this->input('tag_ids') ?? []) : null;
    }

    public function imagesPayloadOrNull(): ?array
    {
        return $this->has('images') ? ($this->file('images') ?? []) : null;
    }

    public function deleteImageIdsOrNull(): ?array
    {
        return $this->has('delete_image_ids') ? ($this->input('delete_image_ids') ?? []) : null;
    }
}

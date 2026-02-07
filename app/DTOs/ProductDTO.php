<?php

namespace App\DTOs;

use App\Models\Product;

class ProductDTO extends BaseDTO
{
    public $id;
    public $company_user_id;
    public $category_id;

    public $name;
    public $sku;
    public $description;
    public $unit_name;
    public $base_price;
    public $is_active;
    public $main_image;

    // ✅ علاقات للعرض
    public $category;   // {id, name}
    public $tags;       // [{id, name, slug}]
    public $images;     // [{id, path, sort_order}]
    public $company;    // {id, name, company_name}

    public $created_at;
    public $updated_at;

    public function __construct(
        $id,
        $company_user_id,
        $category_id,
        $name,
        $sku,
        $description,
        $unit_name,
        $base_price,
        $is_active,
        $main_image,
        $category = null,
        $tags = [],
        $images = [],
        $company = null,
        $created_at = null,
        $updated_at = null
    ) {
        $this->id = $id;
        $this->company_user_id = $company_user_id;
        $this->category_id = $category_id;

        $this->name = $name;
        $this->sku = $sku;
        $this->description = $description;
        $this->unit_name = $unit_name;
        $this->base_price = $base_price;
        $this->is_active = (bool) $is_active;
        $this->main_image = $main_image;

        $this->category = $category;
        $this->tags = $tags;
        $this->images = $images;
        $this->company = $company;

        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromModel(Product $product): self
    {
        $category = $product->category;
        $company  = $product->company; // العلاقة موجودة في Product.php عندك
        $profile  = $company?->companyProfile;

        return new self(
            $product->id,
            $product->company_user_id ?? null,
            $product->category_id ?? null,

            $product->name ?? null,
            $product->sku ?? null,
            $product->description ?? null,
            $product->unit_name ?? null,
            $product->base_price ?? 0,
            $product->is_active ?? false,
            $product->main_image ?? null,

            // ✅ category
            $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,

            // ✅ tags
            $product->tags
                ? $product->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])->values()->toArray()
                : [],

            // ✅ images (مرتبة تلقائيًا من علاقة images())
            $product->images
                ? $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'sort_order' => $img->sort_order,
                ])->values()->toArray()
                : [],

            // ✅ company (اسم الشركة التجاري + fallback)
            $company ? [
                'id' => $company->id,
                'name' => $company->name, // accessor موجود في User.php
                'company_name' => $profile?->company_name ?? $company->name,
            ] : null,

            $product->created_at?->toDateTimeString() ?? null,
            $product->updated_at?->toDateTimeString() ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_user_id' => $this->company_user_id,
            'category_id' => $this->category_id,

            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'unit_name' => $this->unit_name,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'main_image' => $this->main_image,

            'category' => $this->category,
            'tags' => $this->tags,
            'images' => $this->images,
            'company' => $this->company,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function toIndexArray(): array
    {
        // نسخة خفيفة للقوائم (بدون description الثقيل)
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit_name' => $this->unit_name,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'main_image' => $this->main_image,

            // المطلوب في العرض حتى بالقائمة غالبًا:
            'category' => $this->category,
            'company' => $this->company,
        ];
    }
}
<?php

namespace App\DTOs;

use App\Models\Offer;

class OfferDTO
{
    public $id;
    public $company_user_id;

    public $title;
    public $description;

    public $scope;
    public $status;

    public $start_at;
    public $end_at;

    // ✅ Counts لأفضل أداء في الـ Index
    public $items_count;
    public $targets_count;

    // ✅ علاقات للعرض
    public $items;    // [{...}] (تُملأ فقط إذا كانت العلاقة محمّلة)
    public $targets;  // [{...}] (تُملأ فقط إذا كانت العلاقة محمّلة)
    public $company;  // {id, name, company_name}

    public $created_at;
    public $updated_at;

    public function __construct(
        $id,
        $company_user_id,
        $title,
        $description,
        $scope,
        $status,
        $start_at = null,
        $end_at = null,
        $items_count = 0,
        $targets_count = 0,
        $items = [],
        $targets = [],
        $company = null,
        $created_at = null,
        $updated_at = null
    ) {
        $this->id = $id;
        $this->company_user_id = $company_user_id;

        $this->title = $title;
        $this->description = $description;

        $this->scope = $scope;
        $this->status = $status;

        $this->start_at = $start_at;
        $this->end_at = $end_at;

        $this->items_count = (int) $items_count;
        $this->targets_count = (int) $targets_count;

        $this->items = $items;
        $this->targets = $targets;
        $this->company = $company;

        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public static function fromModel(Offer $offer): self
    {
        // ✅ company (لو محمّلة)
        $company = null;
        if ($offer->relationLoaded('company') && $offer->company) {
            $company = [
                'id' => $offer->company->id,
                'name' => trim(($offer->company->first_name ?? '') . ' ' . ($offer->company->last_name ?? '')),
                'company_name' => $offer->company?->companyProfile?->company_name,
            ];
        }

        // ✅ counts (الأفضل: تأتي من withCount)
        $itemsCount = isset($offer->items_count)
            ? (int) $offer->items_count
            : (($offer->relationLoaded('items') && $offer->items) ? $offer->items->count() : 0);

        $targetsCount = isset($offer->targets_count)
            ? (int) $offer->targets_count
            : (($offer->relationLoaded('targets') && $offer->targets) ? $offer->targets->count() : 0);

        // ✅ items/targets تُبنى فقط إذا العلاقات محمّلة (حتى لا نسبب Queries إضافية)
        $items = [];
        if ($offer->relationLoaded('items') && $offer->items) {
            $items = $offer->items->map(function ($item) {
                $product = null;
                if ($item->relationLoaded('product') && $item->product) {
                    $product = [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'base_price' => $item->product->base_price,
                        'main_image' => $item->product->main_image,
                        'is_active' => (bool) $item->product->is_active,
                    ];
                }

                $bonusProduct = null;
                if ($item->relationLoaded('bonusProduct') && $item->bonusProduct) {
                    $bonusProduct = [
                        'id' => $item->bonusProduct->id,
                        'name' => $item->bonusProduct->name,
                        'sku' => $item->bonusProduct->sku,
                        'base_price' => $item->bonusProduct->base_price,
                        'main_image' => $item->bonusProduct->main_image,
                        'is_active' => (bool) $item->bonusProduct->is_active,
                    ];
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'min_qty' => $item->min_qty,
                    'reward_type' => $item->reward_type,

                    'discount_percent' => $item->discount_percent,
                    'discount_fixed' => $item->discount_fixed,

                    'bonus_product_id' => $item->bonus_product_id,
                    'bonus_qty' => $item->bonus_qty,

                    'product' => $product,
                    'bonus_product' => $bonusProduct,
                ];
            })->values()->toArray();
        }

        $targets = [];
        if ($offer->relationLoaded('targets') && $offer->targets) {
            $targets = $offer->targets->map(function ($target) {
                return [
                    'id' => $target->id,
                    'target_type' => $target->target_type,
                    'target_id' => $target->target_id,
                ];
            })->values()->toArray();
        }

        return new self(
            $offer->id,
            $offer->company_user_id,
            $offer->title,
            $offer->description,
            $offer->scope,
            $offer->status,
            optional($offer->start_at)->toDateTimeString(),
            optional($offer->end_at)->toDateTimeString(),
            $itemsCount,
            $targetsCount,
            $items,
            $targets,
            $company,
            optional($offer->created_at)->toDateTimeString(),
            optional($offer->updated_at)->toDateTimeString(),
        );
    }

    /**
     * ✅ للـ Index: خفيف جدًا (يعتمد على withCount)
     */
    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'scope' => $this->scope,
            'status' => $this->status,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'items_count' => $this->items_count,
            'targets_count' => $this->targets_count,
            'company' => $this->company,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * ✅ Show/Edit: كامل
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_user_id' => $this->company_user_id,

            'title' => $this->title,
            'description' => $this->description,

            'scope' => $this->scope,
            'status' => $this->status,

            'start_at' => $this->start_at,
            'end_at' => $this->end_at,

            'items_count' => $this->items_count,
            'targets_count' => $this->targets_count,

            'items' => $this->items,
            'targets' => $this->targets,
            'company' => $this->company,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
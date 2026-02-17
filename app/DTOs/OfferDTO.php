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
            $fullName = trim(($offer->company->first_name ?? '') . ' ' . ($offer->company->last_name ?? ''));
            $company = [
                'id' => $offer->company->id,
                'name' => $fullName ?: 'N/A',
                'company_name' => $offer->company?->companyProfile?->company_name ?? null,
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
                    'target_name' => $target->target_name, // استخدام accessor
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

    /**
     * ✅ تسطيح البيانات: كل منتج في العرض يصبح صف منفصل مع تفاصيل العرض
     * بدلاً من عرض واحد يحتوي على قائمة منتجات، نحصل على قائمة مسطحة
     * كل عنصر يحتوي على: معلومات العرض + معلومات المنتج + معلومات الشركة + المستهدفين
     * 
     * @param int|null $maxItems الحد الأقصى لعدد المنتجات (null = بدون حد)
     * @param bool $groupProducts هل نجمع المنتجات في قائمة داخلية (افتراضي: false)
     */
    public function toFlattenedArray(?int $maxItems = null, bool $groupProducts = false): array
    {
        // إذا كان groupProducts = true، نرجع شكل مختلف
        if ($groupProducts) {
            return $this->toFlattenedWithGroupedProducts($maxItems);
        }

        $flattenedItems = [];

        // معلومات العرض الأساسية (ستتكرر مع كل منتج)
        $offerBase = [
            'offer_id' => $this->id,
            'offer_title' => $this->title,
            'offer_description' => $this->description,
            'offer_scope' => $this->scope,
            'offer_status' => $this->status,
            'offer_start_at' => $this->start_at,
            'offer_end_at' => $this->end_at,
            'offer_created_at' => $this->created_at,
            'offer_updated_at' => $this->updated_at,
        ];

        // معلومات الشركة (ستتكرر مع كل منتج)
        $companyData = [
            'company_id' => $this->company['id'] ?? null,
            'company_name' => $this->company['name'] ?? null,
            'company_business_name' => $this->company['company_name'] ?? null,
        ];

        // تسطيح المستهدفين: تحويلهم إلى حقول منفصلة
        $targetsData = $this->flattenTargets();

        // إذا لم يكن هناك items، نرجع العرض بدون منتجات
        if (empty($this->items)) {
            return [
                array_merge(
                    $offerBase,
                    $companyData,
                    $targetsData,
                    [
                        'item_id' => null,
                        'product_id' => null,
                        'product_name' => null,
                        'product_sku' => null,
                        'product_base_price' => null,
                        'product_main_image' => null,
                        'product_is_active' => null,
                        'min_qty' => null,
                        'reward_type' => null,
                        'discount_percent' => null,
                        'discount_fixed' => null,
                        'bonus_product_id' => null,
                        'bonus_qty' => null,
                        'bonus_product_name' => null,
                        'bonus_product_sku' => null,
                        'bonus_product_base_price' => null,
                        'bonus_product_main_image' => null,
                        'bonus_product_is_active' => null,
                    ]
                )
            ];
        }

        // تحديد عدد المنتجات المراد معالجتها
        $itemsToProcess = $maxItems !== null ? array_slice($this->items, 0, $maxItems) : $this->items;

        // لكل منتج في العرض، نضيف صف منفصل
        foreach ($itemsToProcess as $item) {
            $itemData = [
                'item_id' => $item['id'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'min_qty' => $item['min_qty'] ?? null,
                'reward_type' => $item['reward_type'] ?? null,
                'discount_percent' => $item['discount_percent'] ?? null,
                'discount_fixed' => $item['discount_fixed'] ?? null,
                'bonus_product_id' => $item['bonus_product_id'] ?? null,
                'bonus_qty' => $item['bonus_qty'] ?? null,
            ];

            // معلومات المنتج
            $productData = [
                'product_name' => $item['product']['name'] ?? null,
                'product_sku' => $item['product']['sku'] ?? null,
                'product_base_price' => $item['product']['base_price'] ?? null,
                'product_main_image' => $item['product']['main_image'] ?? null,
                'product_is_active' => $item['product']['is_active'] ?? null,
            ];

            // معلومات المنتج المكافأة (إذا وجد)
            $bonusProductData = [
                'bonus_product_name' => $item['bonus_product']['name'] ?? null,
                'bonus_product_sku' => $item['bonus_product']['sku'] ?? null,
                'bonus_product_base_price' => $item['bonus_product']['base_price'] ?? null,
                'bonus_product_main_image' => $item['bonus_product']['main_image'] ?? null,
                'bonus_product_is_active' => $item['bonus_product']['is_active'] ?? null,
            ];

            // دمج كل البيانات في صف واحد
            $flattenedItems[] = array_merge(
                $offerBase,
                $companyData,
                $targetsData,
                $itemData,
                $productData,
                $bonusProductData
            );
        }

        return $flattenedItems;
    }

    /**
     * تسطيح البيانات مع تجميع المنتجات في قائمة داخلية
     */
    private function toFlattenedWithGroupedProducts(?int $maxItems = null): array
    {
        // معلومات العرض مسطحة
        $data = [
            'offer_id' => $this->id,
            'offer_title' => $this->title,
            'offer_description' => $this->description,
            'offer_scope' => $this->scope,
            'offer_status' => $this->status,
            'offer_start_at' => $this->start_at,
            'offer_end_at' => $this->end_at,
            'offer_created_at' => $this->created_at,
            'offer_updated_at' => $this->updated_at,
        ];

        // معلومات الشركة مسطحة
        $data['company_id'] = $this->company['id'] ?? null;
        $data['company_name'] = $this->company['name'] ?? null;
        $data['company_business_name'] = $this->company['company_name'] ?? null;

        // تسطيح المستهدفين
        $targetsData = $this->flattenTargets();
        $data = array_merge($data, $targetsData);

        // تحديد عدد المنتجات المراد معالجتها
        $itemsToProcess = !empty($this->items) 
            ? ($maxItems !== null ? array_slice($this->items, 0, $maxItems) : $this->items)
            : [];

        $data['items_count'] = count($itemsToProcess);

        // المنتجات في قائمة داخلية (كل منتج مسطح)
        $data['products'] = [];
        foreach ($itemsToProcess as $item) {
            $productData = [
                'item_id' => $item['id'] ?? null,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product']['name'] ?? null,
                'product_sku' => $item['product']['sku'] ?? null,
                'product_base_price' => $item['product']['base_price'] ?? null,
                'product_main_image' => $item['product']['main_image'] ?? null,
                'product_is_active' => $item['product']['is_active'] ?? null,
                'min_qty' => $item['min_qty'] ?? null,
                'reward_type' => $item['reward_type'] ?? null,
                'discount_percent' => $item['discount_percent'] ?? null,
                'discount_fixed' => $item['discount_fixed'] ?? null,
                'bonus_product_id' => $item['bonus_product_id'] ?? null,
                'bonus_qty' => $item['bonus_qty'] ?? null,
                'bonus_product_name' => $item['bonus_product']['name'] ?? null,
                'bonus_product_sku' => $item['bonus_product']['sku'] ?? null,
                'bonus_product_base_price' => $item['bonus_product']['base_price'] ?? null,
                'bonus_product_main_image' => $item['bonus_product']['main_image'] ?? null,
                'bonus_product_is_active' => $item['bonus_product']['is_active'] ?? null,
            ];

            $data['products'][] = $productData;
        }

        return $data;
    }

    /**
     * تسطيح معلومات المستهدفين إلى حقول منفصلة
     */
    private function flattenTargets(): array
    {
        $flattened = [
            'targets_count' => $this->targets_count,
        ];

        // إذا لم يكن هناك targets
        if (empty($this->targets)) {
            return $flattened;
        }

        // تسطيح كل target (فقط الموجودة)
        foreach ($this->targets as $index => $target) {
            $num = $index + 1;
            
            $flattened["target_{$num}_id"] = $target['target_id'] ?? null;
            $flattened["target_{$num}_type"] = $target['target_type'] ?? null;
            $flattened["target_{$num}_name"] = $target['target_name'] ?? null;
        }

        return $flattened;
    }

    /**
     * ✅ تسطيح البيانات بطريقة مختلفة: عرض واحد مع منتجات مسطحة
     * بدلاً من تكرار معلومات العرض مع كل منتج، نعرض العرض مرة واحدة
     * مع معلومات المنتجات كحقول منفصلة (product_1_*, product_2_*, ...)
     * 
     * @param int $maxItems الحد الأقصى لعدد المنتجات (افتراضي: 10)
     */
    public function toFlattenedSingleRow(int $maxItems = 10): array
    {
        // معلومات العرض الأساسية
        $data = [
            'offer_id' => $this->id,
            'offer_title' => $this->title,
            'offer_description' => $this->description,
            'offer_scope' => $this->scope,
            'offer_status' => $this->status,
            'offer_start_at' => $this->start_at,
            'offer_end_at' => $this->end_at,
            'offer_created_at' => $this->created_at,
            'offer_updated_at' => $this->updated_at,
        ];

        // معلومات الشركة
        $data['company_id'] = $this->company['id'] ?? null;
        $data['company_name'] = $this->company['name'] ?? null;
        $data['company_business_name'] = $this->company['company_name'] ?? null;

        // تسطيح المستهدفين
        $targetsData = $this->flattenTargets();
        $data = array_merge($data, $targetsData);

        // تحديد عدد المنتجات المراد معالجتها
        $itemsToProcess = !empty($this->items) ? array_slice($this->items, 0, $maxItems) : [];
        $data['items_count'] = count($itemsToProcess);

        // تسطيح المنتجات
        for ($i = 0; $i < $maxItems; $i++) {
            $num = $i + 1;
            
            if (isset($itemsToProcess[$i])) {
                $item = $itemsToProcess[$i];
                
                // معلومات العنصر
                $data["item_{$num}_id"] = $item['id'] ?? null;
                $data["product_{$num}_id"] = $item['product_id'] ?? null;
                $data["product_{$num}_name"] = $item['product']['name'] ?? null;
                $data["product_{$num}_sku"] = $item['product']['sku'] ?? null;
                $data["product_{$num}_base_price"] = $item['product']['base_price'] ?? null;
                $data["product_{$num}_main_image"] = $item['product']['main_image'] ?? null;
                $data["product_{$num}_is_active"] = $item['product']['is_active'] ?? null;
                $data["product_{$num}_min_qty"] = $item['min_qty'] ?? null;
                $data["product_{$num}_reward_type"] = $item['reward_type'] ?? null;
                $data["product_{$num}_discount_percent"] = $item['discount_percent'] ?? null;
                $data["product_{$num}_discount_fixed"] = $item['discount_fixed'] ?? null;
                $data["product_{$num}_bonus_product_id"] = $item['bonus_product_id'] ?? null;
                $data["product_{$num}_bonus_qty"] = $item['bonus_qty'] ?? null;
                
                // معلومات المنتج المكافأة
                if (!empty($item['bonus_product'])) {
                    $data["product_{$num}_bonus_product_name"] = $item['bonus_product']['name'] ?? null;
                    $data["product_{$num}_bonus_product_sku"] = $item['bonus_product']['sku'] ?? null;
                    $data["product_{$num}_bonus_product_base_price"] = $item['bonus_product']['base_price'] ?? null;
                    $data["product_{$num}_bonus_product_main_image"] = $item['bonus_product']['main_image'] ?? null;
                    $data["product_{$num}_bonus_product_is_active"] = $item['bonus_product']['is_active'] ?? null;
                } else {
                    $data["product_{$num}_bonus_product_name"] = null;
                    $data["product_{$num}_bonus_product_sku"] = null;
                    $data["product_{$num}_bonus_product_base_price"] = null;
                    $data["product_{$num}_bonus_product_main_image"] = null;
                    $data["product_{$num}_bonus_product_is_active"] = null;
                }
            } else {
                // منتج غير موجود - نضع null
                $data["item_{$num}_id"] = null;
                $data["product_{$num}_id"] = null;
                $data["product_{$num}_name"] = null;
                $data["product_{$num}_sku"] = null;
                $data["product_{$num}_base_price"] = null;
                $data["product_{$num}_main_image"] = null;
                $data["product_{$num}_is_active"] = null;
                $data["product_{$num}_min_qty"] = null;
                $data["product_{$num}_reward_type"] = null;
                $data["product_{$num}_discount_percent"] = null;
                $data["product_{$num}_discount_fixed"] = null;
                $data["product_{$num}_bonus_product_id"] = null;
                $data["product_{$num}_bonus_qty"] = null;
                $data["product_{$num}_bonus_product_name"] = null;
                $data["product_{$num}_bonus_product_sku"] = null;
                $data["product_{$num}_bonus_product_base_price"] = null;
                $data["product_{$num}_bonus_product_main_image"] = null;
                $data["product_{$num}_bonus_product_is_active"] = null;
            }
        }

        return $data;
    }
}

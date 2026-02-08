<?php

namespace App\Repositories;

use App\Models\Offer;
use App\Repositories\Eloquent\BaseRepository;

class OfferRepository extends BaseRepository
{
    /**
     * علاقات تُحمَّل افتراضيًا (بأعمدة محددة فقط)
     * الهدف: شاشة إدارة العرض تكون جاهزة (Offer + Items + Targets)
     */
    protected array $defaultWith = [
        // الشركة (مثل نمط ProductRepository)
        'company:id',
        'company.companyProfile:id,user_id,company_name',

        // عناصر العرض
        'items:id,offer_id,product_id,min_qty,reward_type,discount_percent,discount_fixed,bonus_product_id,bonus_qty',
        'items.product:id,company_user_id,category_id,name,sku,base_price,main_image,is_active',
        'items.bonusProduct:id,company_user_id,category_id,name,sku,base_price,main_image,is_active',

        // المستهدفون
        'targets:id,offer_id,target_type,target_id',
    ];

    public function __construct(Offer $model)
    {
        parent::__construct($model);
    }

    /**
     * قائمة العروض (Index)
     */
    public function paginateForIndex(int $perPage = 15)
    {
        return $this->model
            ->with($this->defaultWith)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * عرض واحد للعرض (Show)
     */
    public function findForShow(int $id): Offer
    {
        return $this->model
            ->with($this->defaultWith)
            ->findOrFail($id);
    }

    /**
     * ✅ إن احتجت لاحقًا: فلترة عروض شركة محددة
     * (بدون تعديل BaseRepository)
     */
    public function paginateForCompany(int $companyUserId, int $perPage = 15)
    {
        return $this->model
            ->with($this->defaultWith)
            ->where('company_user_id', $companyUserId)
            ->latest()
            ->paginate($perPage);
    }
}
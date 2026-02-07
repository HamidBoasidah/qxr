<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Eloquent\BaseRepository;

class ProductRepository extends BaseRepository
{
    /**
     * علاقات تُحمَّل افتراضيًا (بأعمدة محددة فقط)
     */
    protected array $defaultWith = [
        'category:id,name',
        'tags:id,name,slug',

        // إذا كانت علاقة الشركة في Product اسمها company()
        'company:id',
        'company.companyProfile:id,user_id,company_name',
    ];

    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * قائمة المنتجات مع الصور (مرتبة تلقائيًا من علاقة images في الموديل)
     */
    public function paginateForIndex(int $perPage = 15)
    {
        return $this->model
            ->with($this->defaultWith)
            ->with(['images:id,product_id,path,sort_order'])
            ->paginate($perPage);
    }

    /**
     * منتج واحد للعرض مع العلاقات المطلوبة + الصور
     */
    public function findForShow(int $id): Product
    {
        return $this->model
            ->with($this->defaultWith)
            ->with(['images:id,product_id,path,sort_order'])
            ->findOrFail($id);
    }
}

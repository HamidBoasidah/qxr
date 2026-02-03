<?php

namespace App\Repositories;

use App\Models\Address;
use App\Repositories\Eloquent\BaseRepository;

class AddressRepository extends BaseRepository
{
    /**
     * العلاقات التي نحتاجها تقريبًا في كل مكان (Admin + API)
     */
    protected array $defaultWith = [
        'user:id,first_name,last_name',
        'governorate:id,name_ar,name_en',
        'district:id,name_ar,name_en',
        'area:id,name_ar,name_en',
    ];

    public function __construct(Address $model)
    {
        parent::__construct($model);
    }

    // لا حاجة لأي دوال إضافية هنا الآن
}

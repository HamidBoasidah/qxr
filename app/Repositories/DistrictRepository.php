<?php

namespace App\Repositories;

use App\Models\District;
use App\Repositories\Eloquent\BaseRepository;

class DistrictRepository extends BaseRepository
{
    protected array $defaultWith = [
        'governorate' => ['id', 'name_ar', 'name_en'],
    ];

    public function __construct(District $model)
    {
        parent::__construct($model);
    }
}

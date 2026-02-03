<?php

namespace App\Repositories;

use App\Models\Area;
use App\Repositories\Eloquent\BaseRepository;

class AreaRepository extends BaseRepository
{
    protected array $defaultWith = [
        'district' => ['id', 'name_ar', 'name_en'],
    ];

    public function __construct(Area $model)
    {
        parent::__construct($model);
    }
}

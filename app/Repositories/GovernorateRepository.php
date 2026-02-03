<?php

namespace App\Repositories;

use App\Models\Governorate;
use App\Repositories\Eloquent\BaseRepository;

class GovernorateRepository extends BaseRepository
{
    public function __construct(Governorate $model)
    {
        parent::__construct($model);
    }

    // مثال توسيع مخصص:
    public function active()
    {
        return $this->query()->where('is_active', true)->get();
    }
}

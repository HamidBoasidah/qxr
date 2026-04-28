<?php

namespace App\Repositories;

use App\Repositories\Eloquent\BaseRepository;
use App\Models\Advertisement;

class AdvertisementRepository extends BaseRepository
{
    protected array $defaultWith = ['company'];

    public function __construct(Advertisement $model)
    {
        parent::__construct($model);
    }
}

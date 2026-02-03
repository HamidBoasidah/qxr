<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Eloquent\BaseRepository;

class TagRepository extends BaseRepository
{
    protected array $defaultWith = [];

    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }
}

<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Repositories\Eloquent\BaseRepository;

class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

}

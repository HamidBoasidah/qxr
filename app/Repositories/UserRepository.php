<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Eloquent\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * ✅ علاقات تُحمَّل تلقائيًا (Eager Loading)
     * - null في دوال BaseRepository => يستخدم defaultWith
     * - [] => بدون علاقات
     * - ['...'] => علاقات محددة يدويًا
     */
    protected array $defaultWith = [
        'customerProfile',
        'customerProfile.category',
        'companyProfile',
        'companyProfile.category',
    ];

    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
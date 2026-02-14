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

    /**
     * البحث عن المستخدمين بالاسم أو البريد الإلكتروني
     */
    public function search(?string $search = null, int $perPage = 15, ?array $with = null)
    {
        $query = $this->query($with);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
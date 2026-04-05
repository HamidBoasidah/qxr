<?php

namespace App\Repositories;

use App\Models\ReturnPolicy;
use App\Repositories\Eloquent\BaseRepository;

class ReturnPolicyRepository extends BaseRepository
{
    /**
     * العلاقات التي تُحمَّل افتراضيًا
     */
    protected array $defaultWith = [
        'company:id,first_name,last_name',
    ];

    public function __construct(ReturnPolicy $model)
    {
        parent::__construct($model);
    }

    /**
     * إرجاع العلاقات الافتراضية لاستخدامها خارج الـ Repository
     */
    public function getDefaultWith(): array
    {
        return $this->defaultWith;
    }

    /**
     * جلب سياسات شركة معينة مع pagination
     */
    public function paginateForCompany(int $companyId, int $perPage = 10, ?array $with = null)
    {
        return $this->makeQuery($with)
            ->where('company_id', $companyId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جلب سياسة واحدة تخص شركة معينة أو رمي ModelNotFoundException
     */
    public function findForCompany(int $id, int $companyId, ?array $with = null): ReturnPolicy
    {
        return $this->makeQuery($with)
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    /**
     * التحقق من وجود سياسة افتراضية نشطة للشركة
     */
    public function hasDefaultPolicy(int $companyId): bool
    {
        return ReturnPolicy::where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * جلب السياسة الافتراضية النشطة للشركة
     */
    public function getDefaultForCompany(int $companyId): ?ReturnPolicy
    {
        return ReturnPolicy::where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }
}

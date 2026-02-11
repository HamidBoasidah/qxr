<?php

namespace App\Repositories;

use App\Models\Offer;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;

class OfferRepository extends BaseRepository
{
    /**
     * تحميل خفيف للـ Index
     */
    protected array $indexWith = [
        'company.companyProfile',
    ];

    /**
     * تحميل كامل للـ Show/Edit
     */
    protected array $showWith = [
        'company.companyProfile',
        'items.product',
        'items.bonusProduct',
        'targets',
    ];

    public function __construct(Offer $model)
    {
        parent::__construct($model);
    }

    public function forCompany(int $companyUserId): Builder
    {
        return $this->model->newQuery()->where('company_user_id', $companyUserId);
    }

    /**
     * ✅ Index (خفيف + counts بأفضل أداء)
     */
    public function paginateForIndex(int $perPage = 15, ?int $companyUserId = null)
    {
        $query = $this->model->newQuery()
            ->with($this->indexWith)
            ->withCount(['items', 'targets'])
            ->latest();

        if ($companyUserId !== null) {
            $query->where('company_user_id', $companyUserId);
        }

        return $query->paginate($perPage);
    }

    /**
     * ✅ Show/Edit (تفاصيل كاملة)
     */
    public function findForShow(int $id): Offer
    {
        return $this->model
            ->with($this->showWith)
            ->withCount(['items', 'targets'])
            ->findOrFail($id);
    }

    public function findPlain(int $id): Offer
    {
        return $this->model->newQuery()->findOrFail($id);
    }
}
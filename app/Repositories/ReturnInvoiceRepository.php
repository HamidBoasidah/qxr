<?php

namespace App\Repositories;

use App\Models\ReturnInvoice;
use App\Repositories\Eloquent\BaseRepository;

class ReturnInvoiceRepository extends BaseRepository
{
    /**
     * العلاقات التي تُحمَّل افتراضيًا
     */
    protected array $defaultWith = [
        'company:id,first_name,last_name',
        'originalInvoice:id,invoice_no,issued_at,status,total_snapshot',
        'returnPolicy:id,name,return_window_days,max_return_ratio',
        'items',
    ];

    public function __construct(ReturnInvoice $model)
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
     * جلب فواتير الاسترجاع لشركة معينة مع pagination
     */
    public function paginateForCompany(int $companyId, int $perPage = 15, ?array $with = null)
    {
        return $this->makeQuery($with)
            ->where('company_id', $companyId)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جلب فاتورة استرجاع واحدة تخص شركة معينة أو رمي ModelNotFoundException
     */
    public function findForCompany(int $id, int $companyId, ?array $with = null): ReturnInvoice
    {
        return $this->makeQuery($with)
            ->where('company_id', $companyId)
            ->findOrFail($id);
    }

    /**
     * التحقق من وجود فاتورة استرجاع لفاتورة أصلية معينة
     */
    public function existsForInvoice(int $originalInvoiceId): bool
    {
        return ReturnInvoice::where('original_invoice_id', $originalInvoiceId)->exists();
    }
}

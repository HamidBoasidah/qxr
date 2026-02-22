<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Eloquent\BaseRepository;

class InvoiceRepository extends BaseRepository
{
    /**
     * العلاقات التي تُحمَّل افتراضيًا
     */
    protected array $defaultWith = [
        'order:id,order_no,status,company_user_id,customer_user_id,submitted_at,approved_at,delivered_at',
        'order.company:id,first_name,last_name',
        'order.customer:id,first_name,last_name',
    ];

    public function __construct(Invoice $model)
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
}

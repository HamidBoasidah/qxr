<?php

namespace App\Services;

use App\Repositories\ActivityLogRepository;

class ActivityLogService
{
    protected ActivityLogRepository $logs;

    public function __construct(ActivityLogRepository $logs)
    {
        $this->logs = $logs;
    }

    public function all(array $with = [])
    {
        return $this->logs->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        
        return $this->logs->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->logs->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        // نادر الاستخدام مع ActivityLog لأن التسجيل يتم عبر activity()
        return $this->logs->create($attributes);
    }

    public function update($id, array $attributes)
    {
        // عادة لا نعدّل سجلات النشاط، لكن أبقيناه لاتباع نفس النمط
        return $this->logs->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->logs->delete($id);
    }
}

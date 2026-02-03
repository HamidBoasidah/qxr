<?php

namespace App\Services;

use App\Repositories\GovernorateRepository;

class GovernorateService
{
    protected GovernorateRepository $governorates;

    public function __construct(GovernorateRepository $governorates)
    {
        $this->governorates = $governorates;
    }

    public function all(array $with = [])
    {
        return $this->governorates->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->governorates->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->governorates->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return $this->governorates->create($attributes);
    }

    public function update($id, array $attributes)
    {
        return $this->governorates->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->governorates->delete($id);
    }

    public function activate($id)
    {
        return $this->governorates->activate($id);
    }

    public function deactivate($id)
    {
        return $this->governorates->deactivate($id);
    }
}

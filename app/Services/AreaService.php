<?php

namespace App\Services;

use App\Repositories\AreaRepository;

class AreaService
{
    protected AreaRepository $areas;

    public function __construct(AreaRepository $areas)
    {
        $this->areas = $areas;
    }

    public function all(array $with = [])
    {
        return $this->areas->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->areas->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->areas->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return $this->areas->create($attributes);
    }

    public function update($id, array $attributes)
    {
        return $this->areas->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->areas->delete($id);
    }

    public function activate($id)
    {
        return $this->areas->activate($id);
    }

    public function deactivate($id)
    {
        return $this->areas->deactivate($id);
    }
}

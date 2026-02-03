<?php

namespace App\Services;

use App\Repositories\DistrictRepository;

class DistrictService
{
    protected DistrictRepository $districts;

    public function __construct(DistrictRepository $districts)
    {
        $this->districts = $districts;
    }

    public function all(array $with = [])
    {
        return $this->districts->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->districts->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->districts->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return $this->districts->create($attributes);
    }

    public function update($id, array $attributes)
    {
        return $this->districts->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->districts->delete($id);
    }

    public function activate($id)
    {
        return $this->districts->activate($id);
    }

    public function deactivate($id)
    {
        return $this->districts->deactivate($id);
    }
}

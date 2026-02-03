<?php

namespace App\Services;

use App\Repositories\PermissionRepository;

class PermissionService
{
    protected PermissionRepository $permissions;

    public function __construct(PermissionRepository $permissions)
    {
        $this->permissions = $permissions;
    }

    public function all(array $with = [])
    {
        return $this->permissions->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->permissions->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->permissions->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return $this->permissions->create($attributes);
    }

    public function update($id, array $attributes)
    {
        return $this->permissions->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->permissions->delete($id);
    }
}

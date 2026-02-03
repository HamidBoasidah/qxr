<?php

namespace App\Services;

use App\Repositories\AdminRepository;

class AdminService
{
    protected AdminRepository $admins;

    public function __construct(AdminRepository $admins)
    {
        $this->admins = $admins;
    }

    public function all(array $with = [])
    {
        return $this->admins->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->admins->paginate($perPage, $with);
    }

    public function find($id, array $with = [])
    {
        return $this->admins->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        // Hash password if provided
        if (isset($attributes['password']) && ! empty($attributes['password'])) {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        return $this->admins->create($attributes);
    }

    public function update($id, array $attributes)
    {
        // Don't update password if empty
        if (array_key_exists('password', $attributes) && empty($attributes['password'])) {
            unset($attributes['password']);
        } elseif (isset($attributes['password'])) {
            $attributes['password'] = bcrypt($attributes['password']);
        }

        return $this->admins->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->admins->delete($id);
    }

    public function activate($id)
    {
        return $this->admins->activate($id);
    }

    public function deactivate($id)
    {
        return $this->admins->deactivate($id);
    }

    public function assignRole($id, string $roleName)
    {
        return $this->admins->assignRoleByName($id, $roleName);
    }
}

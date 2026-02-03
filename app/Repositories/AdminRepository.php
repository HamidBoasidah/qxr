<?php

namespace App\Repositories;

use App\Models\Admin;
use App\Models\Role;
use App\Repositories\Eloquent\BaseRepository;

class AdminRepository extends BaseRepository
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    public function create(array $data)
    {
        $admin = parent::create($data);

        if (isset($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if ($role) {
                $admin->assignRole($role->name);
            }
        }

        return $admin;
    }

    public function update($id, array $data)
    {
        $admin = parent::update($id, $data);

        if (isset($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if ($role) {
                $admin->syncRoles([$role->name]);
            }
        }

        return $admin;
    }

    public function assignRoleByName($id, string $roleName)
    {
        $admin = $this->find($id);
        if ($admin) {
            $admin->assignRole($roleName);
        }
        return $admin;
    }
}

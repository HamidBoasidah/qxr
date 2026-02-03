<?php

namespace App\DTOs;

use App\Models\Admin;

class AdminDTO extends BaseDTO
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $address;
    public $phone_number;
    public $whatsapp_number;
    public $facebook;
    public $x_url;
    public $linkedin;
    public $instagram;
    public $is_active;
    public $locale;
    public $avatar;
    public $role;
    public $role_id;
    public $created_by;
    public $updated_by;
    public $created_at;
    public $deleted_at;

    public function __construct($id, $first_name, $last_name, $email, $address, $phone_number, $whatsapp_number, $facebook, $x_url, $linkedin, $instagram, $is_active, $locale, $avatar, $role, $role_id, $created_by, $updated_by, $created_at = null, $deleted_at = null)
    {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->address = $address;
        $this->phone_number = $phone_number;
        $this->whatsapp_number = $whatsapp_number;
        $this->facebook = $facebook;
        $this->x_url = $x_url;
        $this->linkedin = $linkedin;
        $this->instagram = $instagram;
        $this->is_active = $is_active;
        $this->locale = $locale;
        $this->avatar = $avatar;
        $this->role = $role;
        $this->role_id = $role_id;
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(Admin $admin): self
    {
        $admin->loadMissing(['roles']);
        $role = $admin->roles->first();

        return new self(
            $admin->id,
            $admin->first_name ?? null,
            $admin->last_name ?? null,
            $admin->email ?? null,
            $admin->address ?? null,
            $admin->phone_number ?? null,
            $admin->whatsapp_number ?? null,
            $admin->facebook ?? null,
            $admin->x_url ?? null,
            $admin->linkedin ?? null,
            $admin->instagram ?? null,
            (bool) ($admin->is_active ?? false),
            $admin->locale ?? null,
            $admin->avatar ?? null,
            $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->getTranslations('display_name'),
            ] : null,
            $role?->id,
            $admin->created_by ?? null,
            $admin->updated_by ?? null,
            $admin->created_at?->toDateTimeString() ?? null,
            $admin->deleted_at?->toDateTimeString() ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'whatsapp_number' => $this->whatsapp_number,
            'facebook' => $this->facebook,
            'x_url' => $this->x_url,
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'is_active' => $this->is_active,
            'locale' => $this->locale,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'role_id' => $this->role_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'avatar' => $this->avatar,
            'role' => $this->role,
        ];
    }
}

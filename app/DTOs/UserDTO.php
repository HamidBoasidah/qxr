<?php

namespace App\DTOs;

use App\Models\User;
 
class UserDTO extends BaseDTO
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
    
    public $created_by;
    public $updated_by;
    public $created_at;
    public $deleted_at;

    public function __construct($id, $first_name, $last_name, $email, $address, $phone_number, $whatsapp_number, $facebook, $x_url, $linkedin, $instagram, $is_active, $locale, $avatar, $created_by, $updated_by, $created_at = null, $deleted_at = null)
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
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(User $user): self
    {
        return new self(
            $user->id,
            $user->first_name ?? null,
            $user->last_name ?? null,
            $user->email ?? null,
            $user->address ?? null,
            $user->phone_number ?? null,
            $user->whatsapp_number ?? null,
            $user->facebook ?? null,
            $user->x_url ?? null,
            $user->linkedin ?? null,
            $user->instagram ?? null,
            (bool) ($user->is_active ?? false),
            $user->locale ?? null,
            $user->avatar ?? null,
            $user->created_by ?? null,
            $user->updated_by ?? null,
            $user->created_at?->toDateTimeString() ?? null,
            $user->deleted_at?->toDateTimeString() ?? null
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
            
        ];
    }
}

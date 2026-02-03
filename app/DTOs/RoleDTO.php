<?php

namespace App\DTOs;

use App\Models\Role;

class RoleDTO extends BaseDTO
{
    public $id;
    public $name;
    public $display_name;

    public function __construct($id, $name, $display_name = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->display_name = $display_name;
    }

    public static function fromModel(Role $r): self
    {
        return new self(
            $r->id,
            $r->name,
            method_exists($r, 'getTranslations') ? $r->getTranslations('display_name') : null
        );
    }
}

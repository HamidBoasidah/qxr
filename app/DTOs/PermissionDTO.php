<?php

namespace App\DTOs;

use App\Models\Permission;

class PermissionDTO extends BaseDTO
{
    public $id;
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function fromModel(Permission $p): self
    {
        return new self($p->id, $p->name);
    }
}

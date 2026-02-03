<?php

namespace App\DTOs;

use App\Models\Governorate;

class GovernorateDTO extends BaseDTO
{
    public $id;
    public $name_ar;
    public $name_en;
    public $is_active;
    public $created_by;
    public $updated_by;

    public function __construct($id, $name_ar, $name_en, $is_active, $created_by, $updated_by)
    {
        $this->id = $id;
        $this->name_ar = $name_ar;
        $this->name_en = $name_en;
        $this->is_active = $is_active;
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
    }

    public static function fromModel(Governorate $gov): self
    {
        return new self(
            $gov->id,
            $gov->name_ar,
            $gov->name_en,
            $gov->is_active,
            $gov->created_by,
            $gov->updated_by
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'is_active' => $this->is_active,
        ];
    }
}

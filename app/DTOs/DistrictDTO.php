<?php

namespace App\DTOs;

use App\Models\District;

class DistrictDTO extends BaseDTO
{
    public $id;
    public $name_ar;
    public $name_en;
    public $is_active;
    public $governorate_id;
    public $governorate_name_ar;
    public $governorate_name_en;
    public $created_by;
    public $updated_by;

    public function __construct($id, $name_ar, $name_en, $is_active, $governorate_id, $created_by, $updated_by, $governorate_name_ar = null, $governorate_name_en = null)
    {
        $this->id = $id;
        $this->name_ar = $name_ar;
        $this->name_en = $name_en;
        $this->is_active = $is_active;
        $this->governorate_id = $governorate_id;
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->governorate_name_ar = $governorate_name_ar;
        $this->governorate_name_en = $governorate_name_en;
    }

    public static function fromModel(District $district): self
    {
        return new self(
            $district->id,
            $district->name_ar,
            $district->name_en,
            $district->is_active,
            $district->governorate_id,
            $district->created_by,
            $district->updated_by,
            // governorate names (if relation loaded)
            $district->governorate?->name_ar ?? null,
            $district->governorate?->name_en ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'is_active' => $this->is_active,
            'governorate_id' => $this->governorate_id,
            'governorate_name_ar' => $this->governorate_name_ar,
            'governorate_name_en' => $this->governorate_name_en,
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
            'governorate_id' => $this->governorate_id,
            'governorate_name_ar' => $this->governorate_name_ar,
            'governorate_name_en' => $this->governorate_name_en,
        ];
    }
}

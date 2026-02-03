<?php

namespace App\DTOs;

use App\Models\Address;

class AddressDTO extends BaseDTO
{
    public $id;
    public $user_id;
    public $label;
    public $address;
    public $lat;
    public $lang;
    public $is_default;
    public $is_active;
    public $governorate_id;
    public $governorate_name_ar;
    public $governorate_name_en;
    public $district_id;
    public $district_name_ar;
    public $district_name_en;
    public $area_id;
    public $area_name_ar;
    public $area_name_en;
    public $created_by;
    public $updated_by;
    public $created_at;
    public $deleted_at;

    public function __construct(
        $id,
        $user_id,
        $label,
        $address,
        $lat,
        $lang,
        $is_default,
        $is_active,
        $governorate_id,
        $governorate_name_ar,
        $governorate_name_en,
        $district_id,
        $district_name_ar,
        $district_name_en,
        $area_id,
        $area_name_ar,
        $area_name_en,
        $created_by,
        $updated_by,
        $created_at,
        $deleted_at
    ) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->label = $label;
        $this->address = $address;
        $this->lat = $lat;
        $this->lang = $lang;
        $this->is_default = (bool) $is_default;
        $this->is_active = (bool) $is_active;
        $this->governorate_id = $governorate_id;
        $this->governorate_name_ar = $governorate_name_ar;
        $this->governorate_name_en = $governorate_name_en;
        $this->district_id = $district_id;
        $this->district_name_ar = $district_name_ar;
        $this->district_name_en = $district_name_en;
        $this->area_id = $area_id;
        $this->area_name_ar = $area_name_ar;
        $this->area_name_en = $area_name_en;
        $this->created_by = $created_by;
        $this->updated_by = $updated_by;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(Address $address): self
    {
        // Access related names directly (will be null if relation not loaded)
        return new self(
            $address->id,
            $address->user_id,
            $address->label,
            $address->address,
            $address->lat,
            $address->lang,
            $address->is_default ?? false,
            $address->is_active ?? true,
            $address->governorate_id,
            $address->governorate?->name_ar ?? null,
            $address->governorate?->name_en ?? null,
            $address->district_id,
            $address->district?->name_ar ?? null,
            $address->district?->name_en ?? null,
            $address->area_id,
            $address->area?->name_ar ?? null,
            $address->area?->name_en ?? null,
            $address->created_by,
            $address->updated_by,
            $address->created_at?->toDateTimeString(),
            $address->deleted_at?->toDateTimeString()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'label' => $this->label,
            'address' => $this->address,
            'lat' => $this->lat,
            'lang' => $this->lang,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'governorate_id' => $this->governorate_id,
            'governorate_name_ar' => $this->governorate_name_ar,
            'governorate_name_en' => $this->governorate_name_en,
            'district_id' => $this->district_id,
            'district_name_ar' => $this->district_name_ar,
            'district_name_en' => $this->district_name_en,
            'area_id' => $this->area_id,
            'area_name_ar' => $this->area_name_ar,
            'area_name_en' => $this->area_name_en,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    public function toIndexArray(): array
    {
        // Return a flat index representation to match other DTOs (e.g., DistrictDTO)
        return [
            'id' => $this->id,
            'label' => $this->label,
            'address' => $this->address,
            'lat' => $this->lat,
            'lang' => $this->lang,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'governorate_id' => $this->governorate_id,
            'governorate_name_ar' => $this->governorate_name_ar,
            'governorate_name_en' => $this->governorate_name_en,
            'district_id' => $this->district_id,
            'district_name_ar' => $this->district_name_ar,
            'district_name_en' => $this->district_name_en,
            'area_id' => $this->area_id,
            'area_name_ar' => $this->area_name_ar,
            'area_name_en' => $this->area_name_en,
        ];
    }
}

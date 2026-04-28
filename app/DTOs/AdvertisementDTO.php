<?php

namespace App\DTOs;

use App\Models\Advertisement;

class AdvertisementDTO extends BaseDTO
{
    public $id;
    public $title;
    public $description;
    public $image_path;
    public $image_url;
    public $position;
    public $link_url;
    public $link_type;
    public $link_id;
    public $company_user_id;
    public $company_name;
    public $start_date;
    public $end_date;
    public $sort_order;
    public $is_active;
    public $created_at;

    public static function fromModel(Advertisement $ad): self
    {
        $dto = new self();
        $dto->id = $ad->id;
        $dto->title = $ad->title;
        $dto->description = $ad->description;
        $dto->image_path = $ad->image_path;
        $dto->image_url = $ad->image_url;
        $dto->position = $ad->position;
        $dto->link_url = $ad->link_url;
        $dto->link_type = $ad->link_type;
        $dto->link_id = $ad->link_id;
        $dto->company_user_id = $ad->company_user_id;
        $dto->company_name = $ad->company?->first_name . ' ' . $ad->company?->last_name;
        $dto->start_date = $ad->start_date?->format('Y-m-d');
        $dto->end_date = $ad->end_date?->format('Y-m-d');
        $dto->sort_order = $ad->sort_order;
        $dto->is_active = (bool) $ad->is_active;
        $dto->created_at = $ad->created_at?->toDateTimeString();
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'position' => $this->position,
            'link_url' => $this->link_url,
            'link_type' => $this->link_type,
            'link_id' => $this->link_id,
            'company_user_id' => $this->company_user_id,
            'company_name' => $this->company_name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->image_url,
            'position' => $this->position,
            'company_name' => $this->company_name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }

    public function toMobileArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'position' => $this->position,
            'link_url' => $this->link_url,
            'link_type' => $this->link_type,
            'link_id' => $this->link_id,
            'sort_order' => $this->sort_order,
        ];
    }
}

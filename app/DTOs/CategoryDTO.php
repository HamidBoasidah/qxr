<?php

namespace App\DTOs;

use App\Models\Category;

class CategoryDTO extends BaseDTO
{
    public $id;
    public $name;
    public $slug;
    public $is_active;
    public $icon_path;
    public $icon_url;
    public $category_type;
    public $created_at;
    public $deleted_at;

    public function __construct($id, $name, $slug, $is_active, $icon_path, $icon_url, $category_type = null, $created_at = null, $deleted_at = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->is_active = (bool) $is_active;
        $this->icon_path = $icon_path;
        $this->icon_url = $icon_url;
        $this->category_type = $category_type;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(Category $category): self
    {
        return new self(
            $category->id,
            $category->name ?? null,
            $category->slug ?? null,
            $category->is_active ?? false,
            $category->icon_path ?? null,
            $category->icon_url ?? null,
            $category->category_type ?? null,
            $category->created_at?->toDateTimeString() ?? null,
            $category->deleted_at?->toDateTimeString() ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'icon_path' => $this->icon_path,
            'icon_url' => $this->icon_url,
            'category_type' => $this->category_type,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    public function toIndexArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'icon_url' => $this->icon_url,
            'category_type' => $this->category_type,
        ];
    }

    /**
     * Return minimal fields for mobile clients
     */
    public function toMobileArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'icon_url' => $this->icon_url,
            'category_type' => $this->category_type,
        ];
    }
}

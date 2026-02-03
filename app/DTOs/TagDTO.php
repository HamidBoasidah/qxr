<?php

namespace App\DTOs;

use App\Models\Tag;

class TagDTO extends BaseDTO
{
    public $id;
    public $name;
    public $slug;
    public $is_active;
    public $tag_type;
    public $created_at;
    public $deleted_at;

    public function __construct($id, $name, $slug, $is_active, $tag_type = null, $created_at = null, $deleted_at = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->is_active = (bool) $is_active;
        $this->tag_type = $tag_type;
        $this->created_at = $created_at;
        $this->deleted_at = $deleted_at;
    }

    public static function fromModel(Tag $tag): self
    {
        return new self(
            $tag->id,
            $tag->name ?? null,
            $tag->slug ?? null,
            $tag->is_active ?? false,
            $tag->tag_type ?? null,
            $tag->created_at?->toDateTimeString() ?? null,
            $tag->deleted_at?->toDateTimeString() ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'tag_type' => $this->tag_type,
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
            'tag_type' => $this->tag_type,
        ];
    }

    /**
     * Minimal payload for mobile clients
     */
    public function toMobileArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active,
            'tag_type' => $this->tag_type,
        ];
    }
}

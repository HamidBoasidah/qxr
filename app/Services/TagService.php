<?php

namespace App\Services;

use App\Repositories\TagRepository;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;


class TagService
{
    protected TagRepository $tags;

    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    public function all(array $with = [])
    {
        return $this->tags->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->tags->paginate($perPage, $with);
    }

    public function query(?array $with = null): Builder
    {
        return $this->tags->query($with);
    }

    public function find($id, array $with = [])
    {
        return $this->tags->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        // Ensure slug is generated from name when creating
        if (empty($attributes['slug']) && ! empty($attributes['name'])) {
            $attributes['slug'] = $this->makeUniqueSlug($attributes['name']);
        }

        return $this->tags->create($attributes);
    }

    public function update($id, array $attributes)
    {
        // Generate slug from name on update if name provided
        if (! empty($attributes['name'])) {
            $attributes['slug'] = $this->makeUniqueSlug($attributes['name'], $id);
        }

        return $this->tags->update($id, $attributes);
    }

    /**
     * Generate a unique slug for the given name.
     * If $ignoreId is provided, ignore that record when checking uniqueness (useful on update).
     */
    protected function makeUniqueSlug(string $name, $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Tag::where('slug', $slug)->when($ignoreId, function ($q) use ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        })->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }

    public function delete($id)
    {
        return $this->tags->delete($id);
    }

    public function activate($id)
    {
        return $this->tags->activate($id);
    }

    public function deactivate($id)
    {
        return $this->tags->deactivate($id);
    }

    /**
     * جلب جميع الوسوم النشطة للجوال
     */
    public function getActiveForMobile(): Collection
    {
        return Tag::where('is_active', true)
            ->select(['id', 'name', 'slug', 'is_active', 'tag_type'])
            ->get();
    }

    /**
     * Get active tags filtered by tag_type for mobile
     */
    public function getActiveByTypeForMobile(string $type): Collection
    {
        return Tag::where('is_active', true)
            ->where('tag_type', $type)
            ->select(['id', 'name', 'slug', 'is_active', 'tag_type'])
            ->get();
    }
}

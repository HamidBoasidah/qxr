<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use App\Models\Category;
use App\DTOs\CategoryDTO;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

class CategoryService
{
    protected CategoryRepository $categories;
    protected SVGIconService $svgIconService;

    public function __construct(CategoryRepository $categories, SVGIconService $svgIconService)
    {
        $this->categories = $categories;
        $this->svgIconService = $svgIconService;
    }

    public function all(array $with = [])
    {
        return $this->categories->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->categories->paginate($perPage, $with);
    }

    /**
     * Expose an Eloquent query builder for controllers that need to apply
     * additional constraints or filters before pagination.
     */
    public function query(?array $with = null): Builder
    {
        return $this->categories->query($with);
    }

    public function find($id, array $with = [])
    {
        return $this->categories->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        // Ensure slug is generated from name when creating
        if (empty($attributes['slug']) && ! empty($attributes['name'])) {
            $attributes['slug'] = $this->makeUniqueSlug($attributes['name']);
        }

        return $this->categories->create($attributes);
    }

    public function update($id, array $attributes)
    {
        // Generate slug from name on update if name provided
        if (! empty($attributes['name'])) {
            $attributes['slug'] = $this->makeUniqueSlug($attributes['name'], $id);
        }

        return $this->categories->update($id, $attributes);
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

        while (Category::where('slug', $slug)->when($ignoreId, function ($q) use ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        })->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }

    public function delete($id)
    {
        return $this->categories->delete($id);
    }

    public function activate($id)
    {
        return $this->categories->activate($id);
    }

    public function deactivate($id)
    {
        return $this->categories->deactivate($id);
    }

    /**
     * Upload an icon for a category
     */
    public function uploadIcon(int $categoryId, UploadedFile $iconFile): CategoryDTO
    {
        $category = $this->categories->findOrFail($categoryId);
        
        // حذف الأيقونة القديمة إن وجدت
        if ($category->icon_path) {
            $this->svgIconService->deleteIcon($category->icon_path);
        }
        
        // رفع الأيقونة الجديدة
        $iconPath = $this->svgIconService->uploadIcon($iconFile, $categoryId);
        
        // تحديث الفئة
        $updatedCategory = $this->categories->update($categoryId, [
            'icon_path' => $iconPath
        ]);
        
        return CategoryDTO::fromModel($updatedCategory);
    }

    /**
     * Remove icon from a category
     */
    public function removeIcon(int $categoryId): CategoryDTO
    {
        $category = $this->categories->findOrFail($categoryId);
        
        if ($category->icon_path) {
            $this->svgIconService->deleteIcon($category->icon_path);
            
            $updatedCategory = $this->categories->update($categoryId, [
                'icon_path' => null
            ]);
            
            return CategoryDTO::fromModel($updatedCategory);
        }
        
        return CategoryDTO::fromModel($category);
    }

    /**
     * Delete category and its associated icon
     */
    public function deleteWithIcon($id)
    {
        $category = $this->categories->findOrFail($id);
        
        // حذف الأيقونة إن وجدت
        if ($category->icon_path) {
            $this->svgIconService->deleteIcon($category->icon_path);
        }
        
        return $this->categories->delete($id);
    }

    /**
     * جلب جميع الفئات النشطة للجوال
     */
    public function getActiveForMobile(): Collection
    {
        return Category::where('is_active', true)
            ->select(['id', 'name', 'slug', 'is_active', 'icon_path', 'category_type'])
            ->withCount('products')
            ->get();
    }

    /**
     * Get active categories filtered by category_type for mobile
     */
    public function getActiveByTypeForMobile(string $type): Collection
    {
        return Category::where('is_active', true)
            ->where('category_type', $type)
            ->select(['id', 'name', 'slug', 'is_active', 'icon_path', 'category_type'])
            ->withCount('products')
            ->get();
    }

}

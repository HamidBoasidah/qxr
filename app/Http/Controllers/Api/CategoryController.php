<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryService;
use App\DTOs\CategoryDTO;
use App\Http\Traits\SuccessResponse;
use App\Http\Traits\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    use SuccessResponse, ExceptionHandler;

    /**
     * جلب جميع الفئات النشطة
     * GET /api/mobile/categories
     */
    public function index(CategoryService $categoryService)
    {
        $categories = $categoryService->getActiveForMobile();

        $data = $categories->map(function ($category) {
            return CategoryDTO::fromModel($category)->toMobileArray();
        });

        return $this->collectionResponse($data, 'تم جلب قائمة الفئات بنجاح');
    }

    /**
     * Return active categories filtered by category_type
     */
    public function byType(string $type, CategoryService $categoryService)
    {
        $categories = $categoryService->getActiveByTypeForMobile($type);

        $data = $categories->map(function ($category) {
            return CategoryDTO::fromModel($category)->toMobileArray();
        });

        return $this->collectionResponse($data, 'تم جلب قائمة الفئات بنجاح');
    }
}

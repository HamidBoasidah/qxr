<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TagService;
use App\DTOs\TagDTO;
use App\Http\Traits\SuccessResponse;
use App\Http\Traits\ExceptionHandler;

class TagController extends Controller
{
    use SuccessResponse, ExceptionHandler;

    /**
     * جلب جميع الوسوم النشطة
     * GET /api/mobile/tags
     */
    public function index(TagService $tagService)
    {
        $tags = $tagService->getActiveForMobile();

        $data = $tags->map(function ($tag) {
            return TagDTO::fromModel($tag)->toMobileArray();
        });

        return $this->collectionResponse($data, 'تم جلب قائمة الوسوم بنجاح');
    }

    /**
     * Return active tags filtered by tag_type
     */
    public function byType(string $type, TagService $tagService)
    {
        $tags = $tagService->getActiveByTypeForMobile($type);

        $data = $tags->map(function ($tag) {
            return TagDTO::fromModel($tag)->toMobileArray();
        });

        return $this->collectionResponse($data, 'تم جلب قائمة الوسوم بنجاح');
    }
}

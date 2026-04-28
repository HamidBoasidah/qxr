<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdvertisementService;
use App\DTOs\AdvertisementDTO;
use App\Http\Traits\SuccessResponse;
use App\Http\Traits\ExceptionHandler;

class AdvertisementController extends Controller
{
    use SuccessResponse, ExceptionHandler;

    /**
     * جلب كل الإعلانات النشطة
     * GET /api/advertisements
     */
    public function index(AdvertisementService $service)
    {
        $ads = $service->getAllActive();
        $data = $ads->map(fn($ad) => AdvertisementDTO::fromModel($ad)->toMobileArray());
        return $this->collectionResponse($data, 'تم جلب الإعلانات بنجاح');
    }

    /**
     * جلب الإعلانات حسب الموقع
     * GET /api/advertisements/position/{position}
     */
    public function byPosition(string $position, AdvertisementService $service)
    {
        $ads = $service->getActiveByPosition($position);
        $data = $ads->map(fn($ad) => AdvertisementDTO::fromModel($ad)->toMobileArray());
        return $this->collectionResponse($data, 'تم جلب الإعلانات بنجاح');
    }
}

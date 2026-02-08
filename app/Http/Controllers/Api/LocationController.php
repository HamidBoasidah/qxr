<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GovernorateService;
use App\Services\DistrictService;
use App\Services\AreaService;
use App\DTOs\GovernorateDTO;
use App\DTOs\DistrictDTO;
use App\DTOs\AreaDTO;
use App\Http\Traits\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationController extends Controller
{
    use SuccessResponse;

    /**
     * جلب قائمة المحافظات
     */
    public function governorates(GovernorateService $governorateService)
    {
        $governorates = $governorateService->all();

        $data = $governorates->map(function ($gov) {
            return GovernorateDTO::fromModel($gov)->toIndexArray();
        })->values();

        return $this->successResponse($data, 'تم جلب قائمة المحافظات بنجاح');
    }

    /**
     * جلب المديريات التابعة لمحافظة معينة
     */
    public function districts(int $governorateId, DistrictService $districtService, GovernorateService $governorateService)
    {
        try {
            // تأكد من وجود المحافظة أولاً
            $governorateService->find($governorateId);

            $districts = $districtService->all()->where('governorate_id', $governorateId)->values();

            $data = $districts->map(function ($d) {
                return DistrictDTO::fromModel($d)->toIndexArray();
            })->values();

            return $this->successResponse($data, 'تم جلب المديريات بنجاح');
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'المحافظة غير موجودة'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب المديريات'], 500);
        }
    }

    /**
     * جلب المناطق التابعة لمديرية معينة
     */
    public function areas(int $districtId, AreaService $areaService, DistrictService $districtService)
    {
        try {
            // تأكد من وجود المديرية أولاً
            $districtService->find($districtId);

            $areas = $areaService->all()->where('district_id', $districtId)->values();

            $data = $areas->map(function ($a) {
                return AreaDTO::fromModel($a)->toIndexArray();
            })->values();

            return $this->successResponse($data, 'تم جلب المناطق بنجاح');
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'المديرية غير موجودة'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء جلب المناطق'], 500);
        }
    }
}

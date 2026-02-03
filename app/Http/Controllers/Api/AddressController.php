<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressService;
use App\DTOs\AddressDTO;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Http\Traits\CanFilter;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Exceptions\ValidationException as AppValidationException;

class AddressController extends Controller
{
    use ExceptionHandler, SuccessResponse, CanFilter;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * عرض قائمة عناوين المستخدم الحالي مع فلاتر وترقيم
     */
    public function index(Request $request, AddressService $addressService)
    {
        $perPage = (int) $request->get('per_page', 10);
        $userId  = $request->user()->id;

        // Query لعناوين هذا المستخدم فقط (يستفيد من defaultWith في AddressRepository)
        $query = $addressService->getQueryForUser($userId);

        // تطبيق الفلاتر العامة (بحث + مفاتيح خارجية)
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $addresses = $query->latest()->paginate($perPage);

        // تحويل النتائج إلى DTO خفيفة للـ index
        $addresses->getCollection()->transform(function ($address) {
            return AddressDTO::fromModel($address)->toIndexArray();
        });

        return $this->collectionResponse($addresses, 'تم جلب قائمة العناوين بنجاح');
    }

    /**
     * إنشاء عنوان جديد للمستخدم الحالي
     */
    public function store(StoreAddressRequest $request, AddressService $addressService)
    {
        try {
            $data = $request->validated();

            // إجبارياً نربط العنوان بالمستخدم الحالي حتى لو أرسل user_id من العميل
            $data['user_id'] = $request->user()->id;

            $address = $addressService->create($data);

            return $this->createdResponse(
                AddressDTO::fromModel($address)->toArray(),
                'تم إنشاء العنوان بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        }
    }

    /**
     * عرض عنوان واحد للمستخدم الحالي
     */
    public function show(AddressService $addressService, Request $request, $id)
    {
        try {
            $address = $addressService->findForUser(
                $id,
                $request->user()->id,
                // ممكن تمرّر null وتخلي defaultWith يتكفل بالباقي
                ['governorate', 'district', 'area']
            );

            $this->authorize('view', $address);

            return $this->resourceResponse(
                AddressDTO::fromModel($address)->toArray(),
                'تم جلب بيانات العنوان بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        }
    }

    /**
     * تحديث عنوان يخص المستخدم الحالي
     */
    public function update(UpdateAddressRequest $request, AddressService $addressService, $id)
    {
        try {
            $data = $request->validated();

            // أولاً: نجلب العنوان المملوك للمستخدم الحالي
            $address = $addressService->findForUser(
                $id,
                $request->user()->id,
                ['governorate', 'district', 'area']
            );

            // ثانياً: نتحقق من الـ Policy
            $this->authorize('update', $address);

            // نتأكد أن العنوان سيبقى منسوباً للمستخدم الحالي
            $data['user_id'] = $request->user()->id;

            // ثالثاً: نحدّث نفس الـ Model (بدون إعادة استعلام جديد)
            $updated = $addressService->updateModel($address, $data);

            return $this->updatedResponse(
                AddressDTO::fromModel($updated)->toArray(),
                'تم تحديث العنوان بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        }
    }

    /**
     * حذف عنوان يخص المستخدم الحالي (مع التحقق من الحجوزات)
     */
    public function destroy(AddressService $addressService, Request $request, $id)
    {
        $address = null;

        try {
            $address = $addressService->findForUser($id, $request->user()->id);

            $this->authorize('delete', $address);

            // منع حذف عنوان مرتبط بحجوزات
            if (method_exists($address, 'bookings') && $address->bookings()->exists()) {
                $this->throwResourceInUseException('لا يمكن حذف عنوان مرتبط بحجوزات');
            }

            $addressService->delete($address->id);

            return $this->deletedResponse('تم حذف العنوان بنجاح');
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        } catch (QueryException $e) {
            if ($address) {
                $this->handleDatabaseException($e, $address, [
                    'bookings' => 'حجوزات',
                ]);
            }

            throw $e;
        }
    }

    /**
     * تفعيل عنوان يخص المستخدم الحالي
     */
    public function activate(AddressService $addressService, Request $request, $id)
    {
        try {
            $address = $addressService->findForUser($id, $request->user()->id);

            $this->authorize('activate', $address);

            $activated = $addressService->activate($address->id);

            return $this->activatedResponse(
                AddressDTO::fromModel($activated)->toArray(),
                'تم تفعيل العنوان بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        }
    }

    /**
     * تعطيل عنوان يخص المستخدم الحالي
     */
    public function deactivate(AddressService $addressService, Request $request, $id)
    {
        try {
            $address = $addressService->findForUser($id, $request->user()->id);

            $this->authorize('deactivate', $address);

            $deactivated = $addressService->deactivate($address->id);

            return $this->deactivatedResponse(
                AddressDTO::fromModel($deactivated)->toArray(),
                'تم تعطيل العنوان بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        }
    }

    /**
     * تعيين عنوان افتراضي للمستخدم الحالي
     */
    public function setDefault(AddressService $addressService, Request $request, $id)
    {
        try {
            $address = $addressService->findForUser($id, $request->user()->id);

            $this->authorize('update', $address);

            $updated = $addressService->setDefaultForUser($id, $request->user()->id);

            return $this->updatedResponse(
                AddressDTO::fromModel($updated)->toArray(),
                'تم تعيين العنوان كافتراضي'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العنوان المطلوب غير موجود');
        }
    }

    /**
     * الحقول النصّية التي يمكن البحث فيها عبر CanFilter
     */
    protected function getSearchableFields(): array
    {
        return [
            'label',
            'address',
        ];
    }

    /**
     * الفلاتر الخاصة بالمفاتيح الخارجية والقيم المنطقية
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'governorate_id' => 'governorate_id',
            'district_id'    => 'district_id',
            'area_id'        => 'area_id',
            'is_default'     => 'is_default',
            'is_active'      => 'is_active',
        ];
    }
}

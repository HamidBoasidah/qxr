<?php

namespace App\Http\Controllers\Api;

use App\DTOs\OfferDTO;
use App\Exceptions\ValidationException as AppValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreOfferRequest;
use App\Http\Requests\Company\UpdateOfferRequest;
use App\Http\Traits\CanFilter;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Repositories\OfferRepository;
use App\Services\OfferService;
use App\Models\Offer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    use SuccessResponse, ExceptionHandler, CanFilter;

    public function __construct()
    {
        // All endpoints require authentication (handled by routes middleware)
        // Public offers endpoints show public offers but require user to be logged in
    }

    /**
     * قائمة العروض العامة (للمستخدمين المسجلين فقط)
     * تعرض فقط العروض النشطة والعامة
     */
    public function publicIndex(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);

        $query = $offers->query($this->baseWith())
            ->where('scope', 'public')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($offer) => OfferDTO::fromModel($offer)->toIndexArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة العروض العامة بنجاح');
    }

    /**
     * عرض تفاصيل عرض عام (للمستخدمين المسجلين فقط)
     */
    public function publicShow(OfferRepository $offers, $id)
    {
        try {
            $offer = $offers->query($this->showWith())
                ->where('scope', 'public')
                ->where('status', 'active')
                ->findOrFail($id);

            return $this->resourceResponse(
                OfferDTO::fromModel($offer)->toArray(),
                'تم جلب بيانات العرض بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        }
    }

    /**
     * قائمة عروض الشركة المسجلة (للشركة فقط)
     */
    public function index(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);

        $userId = $request->user()->id;

        $query = $offers->query($this->baseWith())
            ->where('company_user_id', $userId);

        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($offer) => OfferDTO::fromModel($offer)->toIndexArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة عروض الشركة بنجاح');
    }

    /**
     * إنشاء عرض جديد (للشركة فقط)
     */
    public function store(StoreOfferRequest $request, OfferService $service)
    {
        try {
            // authorize via policy (checks user_type === 'company')
            $this->authorize('create', Offer::class);

            $data = $request->validatedPayload();
            $data['company_user_id'] = $request->user()->id;

            $offer = $service->create(
                $data,
                $request->itemsPayload(),
                $request->targetsPayload()
            );

            $offer->load($this->showWith());

            return $this->createdResponse(
                OfferDTO::fromModel($offer)->toArray(),
                'تم إنشاء العرض بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        }
    }

    /**
     * عرض تفاصيل عرض (للشركة المالكة فقط)
     */
    public function show(OfferRepository $offers, $id)
    {
        try {
            $offer = $offers->query($this->showWith())
                ->where('company_user_id', Auth::id())
                ->findOrFail($id);

            return $this->resourceResponse(
                OfferDTO::fromModel($offer)->toArray(),
                'تم جلب بيانات العرض بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        }
    }

    /**
     * تحديث عرض (للشركة المالكة فقط)
     */
    public function update(UpdateOfferRequest $request, OfferService $service, OfferRepository $offers, $id)
    {
        try {
            $offer = $offers->findOrFail($id);
            
            // authorize via policy (ownership + company type)
            $this->authorize('update', $offer);

            $offer = $service->update(
                $offer->id,
                $request->validatedPayload(),
                $request->itemsPayloadOrNull(),
                $request->targetsPayloadOrNull()
            );

            $offer->load($this->showWith());

            return $this->updatedResponse(
                OfferDTO::fromModel($offer)->toArray(),
                'تم تحديث العرض بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        }
    }

    /**
     * حذف عرض (للشركة المالكة فقط)
     */
    public function destroy(OfferService $service, OfferRepository $offers, $id)
    {
        try {
            $offer = $offers->findOrFail($id);
            
            // authorize via policy
            $this->authorize('delete', $offer);

            $service->delete($offer->id);
            return $this->deletedResponse('تم حذف العرض بنجاح');
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('العرض المطلوب غير موجود');
        }
    }

    /**
     * العلاقات الأساسية للعرض في القوائم
     */
    protected function baseWith(): array
    {
        return [
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
        ];
    }

    /**
     * العلاقات الكاملة لعرض التفاصيل
     */
    protected function showWith(): array
    {
        return [
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
            'items',
            'items.product:id,name,sku,base_price,main_image,is_active',
            'items.bonusProduct:id,name,sku,base_price,main_image,is_active',
            'targets',
        ];
    }

    /**
     * حقول البحث النصي
     */
    protected function getSearchableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * فلاتر المفاتيح الخارجية والقيم المنطقية
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'scope' => 'scope',
            'status' => 'status',
        ];
    }
}

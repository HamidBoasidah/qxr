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
     * تعرض العروض النشطة العامة أو الخاصة المستهدفة للمستخدم
     */
    public function publicIndex(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);
        $user = $request->user();

        $query = $offers->query($this->baseWith())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        // تطبيق فلتر النطاق: عام أو خاص مستهدف للمستخدم
        $this->applyScopeFilter($query, $user);

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($offer) => OfferDTO::fromModel($offer)->toIndexArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة العروض العامة بنجاح');
    }

    /**
     * قائمة العروض العامة مع التفاصيل الكاملة (للمستخدمين المسجلين فقط)
     * تعرض العروض النشطة العامة أو الخاصة المستهدفة للمستخدم مع items و targets
     * الحد الأقصى: 10 منتجات لكل عرض
     */
    public function publicIndexDetails(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);
        $user = $request->user();

        $query = $offers->query($this->showWith())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        // تطبيق فلتر النطاق: عام أو خاص مستهدف للمستخدم
        $this->applyScopeFilter($query, $user);

        $paginated = $query->latest()->paginate($perPage);

        // تحويل البيانات: بيانات مسطحة مع المنتجات في قائمة داخلية (حد أقصى 10)
        $paginated->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toFlattenedArray(10, true);
        });

        return $this->collectionResponse($paginated, 'تم جلب قائمة العروض العامة مع التفاصيل بنجاح');
    }

    /**
     * قائمة عروض الشركة المسجلة بشكل مسطح تماماً (للشركة فقط)
     * تعرض العروض بشكل مسطح (صف واحد لكل منتج)
     * بدون حد أقصى للمنتجات
     */
    public function indexFlat(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);

        $userId = $request->user()->id;

        $query = $offers->query($this->showWith())
            ->where('company_user_id', $userId);

        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        // تحويل البيانات: صف منفصل لكل منتج (بدون حد أقصى)
        $flattenedData = [];
        foreach ($paginated->items() as $offer) {
            $offerDTO = OfferDTO::fromModel($offer);
            $flatArray = $offerDTO->toFlattenedArray(null, true);
            
            // تحويل كل منتج إلى صف منفصل
            if (!empty($flatArray['products'])) {
                foreach ($flatArray['products'] as $product) {
                    $row = [
                        'offer_id' => $flatArray['offer_id'],
                        'offer_title' => $flatArray['offer_title'],
                        'offer_description' => $flatArray['offer_description'],
                        'offer_scope' => $flatArray['offer_scope'],
                        'offer_status' => $flatArray['offer_status'],
                        'offer_start_at' => $flatArray['offer_start_at'],
                        'offer_end_at' => $flatArray['offer_end_at'],
                        'offer_created_at' => $flatArray['offer_created_at'],
                        'offer_updated_at' => $flatArray['offer_updated_at'],
                        'company_id' => $flatArray['company_id'],
                        'company_name' => $flatArray['company_name'],
                        'company_business_name' => $flatArray['company_business_name'],
                    ];
                    
                    // دمج بيانات المنتج
                    $flattenedData[] = array_merge($row, $product);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة عروض الشركة بشكل مسطح بنجاح',
            'status_code' => 200,
            'data' => $flattenedData,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ], 200);
    }


    /**
     * قائمة العروض العامة بشكل مسطح تماماً (للمستخدمين المسجلين فقط)
     * تعرض العروض النشطة العامة أو الخاصة المستهدفة للمستخدم بشكل مسطح (صف واحد لكل منتج)
     * الحد الأقصى: 10 منتجات لكل عرض
     */
    public function publicIndexFlat(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);
        $user = $request->user();

        $query = $offers->query($this->showWith())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });

        // تطبيق فلتر النطاق: عام أو خاص مستهدف للمستخدم
        $this->applyScopeFilter($query, $user);

        $paginated = $query->latest()->paginate($perPage);

        // تحويل البيانات: صف منفصل لكل منتج (حد أقصى 10 منتجات لكل عرض)
        $flattenedData = [];
        foreach ($paginated->items() as $offer) {
            $offerDTO = OfferDTO::fromModel($offer);
            $flatArray = $offerDTO->toFlattenedArray(10, true);
            
            // تحويل كل منتج إلى صف منفصل
            if (!empty($flatArray['products'])) {
                foreach ($flatArray['products'] as $product) {
                    $row = [
                        'offer_id' => $flatArray['offer_id'],
                        'offer_title' => $flatArray['offer_title'],
                        'offer_description' => $flatArray['offer_description'],
                        'offer_scope' => $flatArray['offer_scope'],
                        'offer_status' => $flatArray['offer_status'],
                        'offer_start_at' => $flatArray['offer_start_at'],
                        'offer_end_at' => $flatArray['offer_end_at'],
                        'offer_created_at' => $flatArray['offer_created_at'],
                        'offer_updated_at' => $flatArray['offer_updated_at'],
                        'company_id' => $flatArray['company_id'],
                        'company_name' => $flatArray['company_name'],
                        'company_business_name' => $flatArray['company_business_name'],
                    ];
                    
                    // دمج بيانات المنتج
                    $flattenedData[] = array_merge($row, $product);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب قائمة العروض العامة بشكل مسطح بنجاح',
            'status_code' => 200,
            'data' => $flattenedData,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ], 200);
    }


    /**
     * عرض تفاصيل عرض عام (للمستخدمين المسجلين فقط)
     */
    public function publicShow(Request $request, OfferRepository $offers, $id)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $user = $request->user();
            
            $query = $offers->query([
                'company:id,first_name,last_name',
                'company.companyProfile:id,user_id,company_name',
                'targets',
            ])
                ->where('status', 'active');

            // تطبيق فلتر النطاق: عام أو خاص مستهدف للمستخدم
            $this->applyScopeFilter($query, $user);

            $offer = $query->findOrFail($id);

            // تحميل المنتجات مع pagination
            $items = $offer->items()
                ->with([
                    'product:id,name,sku,base_price,main_image,is_active',
                    'bonusProduct:id,name,sku,base_price,main_image,is_active'
                ])
                ->paginate($perPage);

            // تحويل البيانات
            $offerData = OfferDTO::fromModel($offer)->toArray();
            
            // استبدال items بالبيانات المقسمة
            $offerData['items'] = $items->items();
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات العرض بنجاح',
                'status_code' => 200,
                'data' => $offerData,
                'items_pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ]
            ], 200);
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
     * قائمة عروض الشركة المسجلة مع التفاصيل الكاملة (للشركة فقط)
     * تعرض العروض مع items و targets بشكل مسطح
     * البيانات مسطحة مع المنتجات في قائمة داخلية (بدون حد أقصى)
     */
    public function indexDetails(Request $request, OfferRepository $offers)
    {
        $perPage = (int) $request->get('per_page', 10);

        $userId = $request->user()->id;

        $query = $offers->query($this->showWith())
            ->where('company_user_id', $userId);

        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        // تحويل البيانات: بيانات مسطحة مع المنتجات في قائمة داخلية (بدون حد)
        $paginated->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toFlattenedArray(null, true);
        });

        return $this->collectionResponse($paginated, 'تم جلب قائمة عروض الشركة مع التفاصيل بنجاح');
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
    public function show(Request $request, OfferRepository $offers, $id)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            
            $offer = $offers->query([
                'company:id,first_name,last_name',
                'company.companyProfile:id,user_id,company_name',
                'targets',
            ])
                ->where('company_user_id', Auth::id())
                ->findOrFail($id);

            // تحميل المنتجات مع pagination
            $items = $offer->items()
                ->with([
                    'product:id,name,sku,base_price,main_image,is_active',
                    'bonusProduct:id,name,sku,base_price,main_image,is_active'
                ])
                ->paginate($perPage);

            // تحويل البيانات
            $offerData = OfferDTO::fromModel($offer)->toArray();
            
            // استبدال items بالبيانات المقسمة
            $offerData['items'] = $items->items();
            
            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات العرض بنجاح',
                'status_code' => 200,
                'data' => $offerData,
                'items_pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ]
            ], 200);
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
            'targets', // target_name سيتم جلبه عبر accessor
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

    /**
     * تطبيق فلتر النطاق: العروض العامة أو الخاصة المستهدفة للمستخدم
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return void
     */
    protected function applyScopeFilter($query, $user): void
    {
        $query->where(function($q) use ($user) {
            // العروض العامة
            $q->where('scope', 'public')
              // أو العروض الخاصة المستهدفة للمستخدم
              ->orWhere(function($subQ) use ($user) {
                  $subQ->where('scope', 'private')
                      ->whereHas('targets', function($targetQ) use ($user) {
                          // مستهدف مباشر (customer)
                          $targetQ->where(function($tq) use ($user) {
                              $tq->where('target_type', 'customer')
                                 ->where('target_id', $user->id);
                          });
                          
                          // أو مستهدف عبر الفئة (customer_category)
                          if ($user->relationLoaded('customerProfile') && $user->customerProfile && $user->customerProfile->category_id) {
                              $targetQ->orWhere(function($tq) use ($user) {
                                  $tq->where('target_type', 'customer_category')
                                     ->where('target_id', $user->customerProfile->category_id);
                              });
                          } elseif (!$user->relationLoaded('customerProfile')) {
                              // تحميل customerProfile إذا لم يكن محملاً
                              $user->load('customerProfile');
                              if ($user->customerProfile && $user->customerProfile->category_id) {
                                  $targetQ->orWhere(function($tq) use ($user) {
                                      $tq->where('target_type', 'customer_category')
                                         ->where('target_id', $user->customerProfile->category_id);
                                  });
                              }
                          }
                          
                          // TODO: دعم customer_tag عندما يتم تطبيق نظام الوسوم للمستخدمين
                      });
              });
        });
    }
}

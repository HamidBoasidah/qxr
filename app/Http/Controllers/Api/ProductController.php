<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CategoryDTO;
use App\DTOs\ProductDTO;
use App\DTOs\TagDTO;
use App\Exceptions\ValidationException as AppValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Traits\CanFilter;
use App\Http\Traits\ExceptionHandler;
use App\Http\Traits\SuccessResponse;
use App\Services\CategoryService;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\TagService;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use SuccessResponse, ExceptionHandler, CanFilter;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * الفئات النشطة الخاصة بالمنتجات (category_type = product)
     */
    public function categories(CategoryService $categories)
    {
        $items = $categories->getActiveByTypeForMobile('product');

        $data = $items->map(fn ($cat) => CategoryDTO::fromModel($cat)->toMobileArray());

        return $this->collectionResponse($data, 'تم جلب فئات المنتجات بنجاح');
    }

    /**
     * الوسوم النشطة الخاصة بالمنتجات (tag_type = product)
     */
    public function tags(TagService $tags)
    {
        $items = $tags->getActiveByTypeForMobile('product');

        $data = $items->map(fn ($tag) => TagDTO::fromModel($tag)->toMobileArray());

        return $this->collectionResponse($data, 'تم جلب وسوم المنتجات بنجاح');
    }

    /**
     * قائمة المنتجات مع فلاتر وترقيم
     */
    public function index(Request $request, ProductRepository $products)
    {
        $perPage = (int) $request->get('per_page', 10);

        $query = $products->query($this->mobileWith());

        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($product) => ProductDTO::fromModel($product)->toMobileArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة المنتجات بنجاح');
    }

    /**
     * جلب منتجات المستخدم المسجل (المرتبطة بشركة المستخدم)
     */
    public function mine(Request $request, ProductRepository $products)
    {
        $perPage = (int) $request->get('per_page', 10);

        $userId = $request->user()->id;

        $query = $products->query($this->mobileWith())
            ->whereHas('company', function ($q) use ($userId) {
                // company() relation points to users table (User model), filter by users.id
                $q->where('id', $userId);
            });

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($product) => ProductDTO::fromModel($product)->toMobileArray());

        return $this->collectionResponse($paginated, 'تم جلب منتجات المستخدم بنجاح');
    }

    /**
     * إنشاء منتج جديد
     */
    public function store(StoreProductRequest $request, ProductService $service)
    {
        try {
            // authorize via policy (checks user_type === 'company')
            $this->authorize('create', Product::class);

            $product = $service->create(
                $request->validatedPayload(),
                $request->tagIds(),
                $request->imagesPayload()
            )->load($this->baseWith());

            return $this->createdResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم إنشاء المنتج بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        }
    }

    /**
     * عرض منتج واحد
     */
    public function show(ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id, $this->mobileWith());

            return $this->resourceResponse(
                ProductDTO::fromModel($product)->toMobileArray(),
                'تم جلب بيانات المنتج بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
    }

    /**
     * تحديث منتج
     */
    public function update(UpdateProductRequest $request, ProductService $service, ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            
            // authorize via policy (ownership + company type)
            $this->authorize('update', $product);

            $product = $service->update(
                $product->id,
                $request->validatedPayload(),
                $request->tagIdsOrNull(),
                $request->imagesPayloadOrNull(),
                $request->deleteImageIdsOrNull()
            )->load($this->baseWith());

            return $this->updatedResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم تحديث المنتج بنجاح'
            );
        } catch (AppValidationException $e) {
            return $e->render($request);
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
    }

    /**
     * حذف منتج
     */
    public function destroy(ProductService $service, ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id);
            
            // authorize via policy
            $this->authorize('delete', $product);

            $service->delete($product->id);
            return $this->deletedResponse('تم حذف المنتج بنجاح');
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
    }

    /**
     * تفعيل منتج
     */
    public function activate(ProductService $service, ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            
            // authorize via policy
            $this->authorize('activate', $product);

            $product = $service->activate($product->id)->load($this->baseWith());
            return $this->activatedResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم تفعيل المنتج بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
    }

    /**
     * تعطيل منتج
     */
    public function deactivate(ProductService $service, ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            
            // authorize via policy
            $this->authorize('deactivate', $product);

            $product = $service->deactivate($product->id)->load($this->baseWith());
            return $this->deactivatedResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم تعطيل المنتج بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        } catch (AuthorizationException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
    }

    /**
     * العلاقات الأساسية للعرض
     */
    protected function baseWith(): array
    {
        return [
            'category:id,name',
            'tags:id,name,slug',
            'company:id',
            'company.companyProfile:id,user_id,company_name',
            'images:id,product_id,path,sort_order',
        ];
    }

    /**
     * العلاقات للموبايل (مع العروض النشطة)
     */
    protected function mobileWith(): array
    {
        return [
            'category:id,name',
            'tags:id,name,slug',
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
            'images:id,product_id,path,sort_order',
            'activeOffers:offers.id,title,description,status,scope,start_at,end_at',
            'activeOffers.items:id,offer_id,product_id,min_qty,reward_type,discount_percent,discount_fixed,bonus_product_id,bonus_qty',
            'activeOffers.items.bonusProduct:id,name,main_image',
        ];
    }

    /**
     * حقول البحث النصي
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'sku', 'description'];
    }

    /**
     * فلاتر المفاتيح الخارجية والقيم المنطقية
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'category_id' => 'category_id',
            'company_user_id' => 'company_user_id',
            'is_active' => 'is_active',
        ];
    }
}

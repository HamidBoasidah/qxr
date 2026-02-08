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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $query = $products->query($this->baseWith());

        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($product) => ProductDTO::fromModel($product)->toIndexArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة المنتجات بنجاح');
    }

    /**
     * إنشاء منتج جديد
     */
    public function store(StoreProductRequest $request, ProductService $service)
    {
        /* commit
        try {
            $user = Auth::user();
            if (!$user || $user->user_type !== 'company') {
                $this->throwForbiddenException('يجب أن تكون شركة لإنشاء منتج');
            }

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
        */

        // تم تعطيل إنشاء المنتجات مؤقتًا
        $this->throwForbiddenException('تم تعطيل عملية الحفظ');
    }

    /**
     * عرض منتج واحد
     */
    public function show(ProductRepository $products, $id)
    {
        try {
            $product = $products->findOrFail($id, $this->baseWith());

            return $this->resourceResponse(
                ProductDTO::fromModel($product)->toArray(),
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
        /* commit
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            $user = Auth::user();
            if (!$user || $user->user_type !== 'company' || (int) $product->company_user_id !== (int) $user->id) {
                $this->throwForbiddenException('غير مصرح لك بتعديل هذا المنتج');
            }

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
        }
        */

        // تم تعطيل تعديل المنتجات مؤقتًا
        $this->throwForbiddenException('تم تعطيل عملية التعديل');
    }

    /**
     * حذف منتج
     */
    public function destroy(ProductService $service, ProductRepository $products, $id)
    {
        /* commit
        try {
            $product = $products->findOrFail($id);
            $user = Auth::user();
            if (!$user || $user->user_type !== 'company' || (int) $product->company_user_id !== (int) $user->id) {
                $this->throwForbiddenException('غير مصرح لك بحذف هذا المنتج');
            }

            $service->delete($product->id);
            return $this->deletedResponse('تم حذف المنتج بنجاح');
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
        */

        // تم تعطيل حذف المنتجات مؤقتًا
        $this->throwForbiddenException('تم تعطيل عملية الحذف');
    }

    /**
     * تفعيل منتج
     */
    public function activate(ProductService $service, ProductRepository $products, $id)
    {
        /* commit
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            $user = Auth::user();
            if (!$user || $user->user_type !== 'company' || (int) $product->company_user_id !== (int) $user->id) {
                $this->throwForbiddenException('غير مصرح لك بتفعيل هذا المنتج');
            }

            $product = $service->activate($product->id)->load($this->baseWith());
            return $this->activatedResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم تفعيل المنتج بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
        */

        // تم تعطيل تفعيل المنتجات مؤقتًا
        $this->throwForbiddenException('تم تعطيل عملية التفعيل');
    }

    /**
     * تعطيل منتج
     */
    public function deactivate(ProductService $service, ProductRepository $products, $id)
    {
        /* commit
        try {
            $product = $products->findOrFail($id, $this->baseWith());
            $user = Auth::user();
            if (!$user || $user->user_type !== 'company' || (int) $product->company_user_id !== (int) $user->id) {
                $this->throwForbiddenException('غير مصرح لك بتعطيل هذا المنتج');
            }

            $product = $service->deactivate($product->id)->load($this->baseWith());
            return $this->deactivatedResponse(
                ProductDTO::fromModel($product)->toArray(),
                'تم تعطيل المنتج بنجاح'
            );
        } catch (ModelNotFoundException) {
            $this->throwNotFoundException('المنتج المطلوب غير موجود');
        }
        */

        // تم تعطيل إلغاء تفعيل المنتجات مؤقتًا
        $this->throwForbiddenException('تم تعطيل عملية إلغاء التفعيل');
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

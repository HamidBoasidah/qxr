<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;

class ProductService
{
    protected ProductRepository $products;

    public function __construct(ProductRepository $products)
    {
        $this->products = $products;
    }

    public function all(array $with = null)
    {
        return $this->products->all($with);
    }

    public function paginate(int $perPage = 15, array $with = null)
    {
        return $this->products->paginate($perPage, $with);
    }

    public function find($id, array $with = null): Product
    {
        return $this->products->findOrFail($id, $with);
    }

    /**
     * إنشاء منتج + ربط الوسوم + إنشاء الصور
     *
     * $productData   => من $request->validatedPayload()
     * $tagIds        => من $request->tagIds()
     * $imagesPayload => من $request->imagesPayload()
     */
    public function create(array $productData, array $tagIds = [], array $imagesPayload = []): Product
    {
        return DB::transaction(function () use ($productData, $tagIds, $imagesPayload) {

            // الشركة تُؤخذ من المستخدم الحالي (ولا نثق بإدخال العميل)
            $productData['company_user_id'] = $productData['company_user_id'] ?? Auth::id();

            /** @var Product $product */
            $product = $this->products->create($productData);

            // ربط الوسوم (اختياري)
            $this->syncTags($product, $tagIds);

            // إنشاء الصور (اختياري)
            $this->replaceImages($product, $imagesPayload);

            return $product->refresh();
        });
    }

    /**
     * تحديث منتج + (اختياري) تحديث الوسوم + (اختياري) تحديث الصور
     *
     * $tagIdsOrNull        => من $request->tagIdsOrNull()
     * $imagesPayloadOrNull => من $request->imagesPayloadOrNull()
     *
     * إذا كانت null => لا نلمس العلاقة
     * إذا كانت []  => نفرغ العلاقة
     */
    public function update($id, array $productData, ?array $tagIdsOrNull = null, ?array $imagesPayloadOrNull = null, ?array $deleteImageIdsOrNull = null): Product
    {
        return DB::transaction(function () use ($id, $productData, $tagIdsOrNull, $imagesPayloadOrNull, $deleteImageIdsOrNull) {

            // ممنوع تعديل الشركة المالكة من العميل
            unset($productData['company_user_id']);

            /** @var Product $product */
            $product = $this->products->findOrFail($id);

            // (اختياري لاحقاً) تحقق ملكية: المنتج يتبع نفس الشركة
            // if ($product->company_user_id !== Auth::id()) abort(403);

            $product = $this->products->updateModel($product, $productData);

            // الوسوم: إذا أرسلها العميل
            if ($tagIdsOrNull !== null) {
                $this->syncTags($product, $tagIdsOrNull);
            }

            // الصور: إذا أرسلها العميل أو أرسل IDs للحذف
            if ($imagesPayloadOrNull !== null || $deleteImageIdsOrNull !== null) {
                $this->updateImages($product, $imagesPayloadOrNull ?? [], $deleteImageIdsOrNull ?? []);
            }

            return $product->refresh();
        });
    }

    public function delete($id): bool
    {
        return $this->products->delete($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * مزامنة الوسوم للمنتج
     */
    private function syncTags(Product $product, array $tagIds): void
    {
        // إذا لم تُرسل أي وسوم => sync([]) سيمسحها كلها
        $product->tags()->sync($tagIds);
    }

    /**
     * استبدال صور المنتج بالكامل
     *
     * ملاحظة: لأن الـ Request الحالي لا يرسل IDs للصور،
     * فالأبسط والأوضح Domain-wise: "استبدال كامل" عند التحديث.
     */
    private function replaceImages(Product $product, array $imagesPayload): void
    {
        // حذف كل الصور القديمة
        $product->images()->delete();

        if (empty($imagesPayload)) {
            return;
        }

        $prepared = [];

        foreach (array_values($imagesPayload) as $index => $image) {
            // UploadedFile directly from the request
            if ($image instanceof UploadedFile) {
                $path = $image->store($product->getTable(), 'public');

                $prepared[] = [
                    'path' => $path,
                    'sort_order' => $index,
                ];

                continue;
            }

            // Already an array with path/sort_order
            if (is_array($image) && isset($image['path'])) {
                $prepared[] = [
                    'path' => $image['path'],
                    'sort_order' => $image['sort_order'] ?? $index,
                ];
            }
        }

        if ($prepared) {
            $product->images()->createMany($prepared);
        }
    }

    /**
     * تحديث صور المنتج: احذف الصور المحددة وأضف الجديدة (لا تستبدل كل الصور تلقائيًا)
     */
    private function updateImages(Product $product, array $newImages = [], array $deleteIds = []): void
    {
        // احذف الصور المحددة فقط
        if (!empty($deleteIds)) {
            $product->images()->whereIn('id', $deleteIds)->delete();
        }

        if (empty($newImages)) {
            return;
        }

        $prepared = [];

        // احتساب sort_order بناءً على العدد الحالي بعد الحذف
        $baseIndex = $product->images()->count();

        foreach (array_values($newImages) as $offset => $image) {
            $index = $baseIndex + $offset;

            if ($image instanceof UploadedFile) {
                $path = $image->store($product->getTable(), 'public');

                $prepared[] = [
                    'path' => $path,
                    'sort_order' => $index,
                ];

                continue;
            }

            if (is_array($image) && isset($image['path'])) {
                $prepared[] = [
                    'path' => $image['path'],
                    'sort_order' => $image['sort_order'] ?? $index,
                ];
            }
        }

        if ($prepared) {
            $product->images()->createMany($prepared);
        }
    }

    public function activate($id)
    {
        return $this->products->activate($id);
    }

    public function deactivate($id)
    {
        return $this->products->deactivate($id);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Services\ProductService;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;

use App\DTOs\ProductDTO;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:products.view')->only(['index', 'show']);
        $this->middleware('permission:products.create')->only(['create', 'store']);
        $this->middleware('permission:products.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:products.delete')->only(['destroy']);
    }

    public function index(Request $request, ProductService $productService)
    {
        $perPage = (int) $request->input('per_page', 10);

        $products = $productService->paginate($perPage);

        // ✅ تجهيز بيانات كل منتج بنفس منطق show ولكن نسخة خفيفة (Index)
        $products->getCollection()->transform(function ($product) {
            return ProductDTO::fromModel($product)->toIndexArray();
        });

        return Inertia::render('Admin/Product/Index', [
            'products' => $products
        ]);
    }

    public function create()
    {
        // ✅ أقسام المنتجات فقط
        $categories = Category::where('category_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name']);

        // ✅ وسوم المنتجات فقط
        $tags = Tag::where('tag_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Product/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $productService)
    {
        // ✅ بنفس أسلوبك الاحترافي: نفصل payload عن العلاقات
        $data = $request->validatedPayload();

        // الشركة من السيرفر (المستخدم الحالي)
        $data['company_user_id'] = Auth::id();

        $productService->create(
            $data,
            $request->tagIds(),
            $request->imagesPayload()
        );

        return redirect()->route('admin.products.index');
    }

    public function show(Product $product)
    {
        // ✅ لضمان أن DTO ما يعمل Lazy Load (ويضمن البيانات التي تريدها للعرض)
        $product->load([
            'category:id,name',
            'tags:id,name,slug',
            'images:id,product_id,path,sort_order',
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
        ]);

        $productDTO = ProductDTO::fromModel($product)->toArray();

        return Inertia::render('Admin/Product/Show', [
            'product' => $productDTO,
        ]);
    }

    public function edit(Product $product)
    {
        $product->load([
            'category:id,name',
            'tags:id,name,slug',
            'images:id,product_id,path,sort_order',
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
        ]);

        $productDTO = ProductDTO::fromModel($product)->toArray();

        $categories = Category::where('category_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $tags = Tag::where('tag_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Product/Edit', [
            'product' => $productDTO,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function update(UpdateProductRequest $request, ProductService $productService, Product $product)
    {
        $data = $request->validatedPayload();

        $productService->update(
            $product->id,
            $data,
            $request->tagIdsOrNull(),
            $request->imagesPayloadOrNull()
        );

        return redirect()->route('admin.products.index');
    }

    public function destroy(ProductService $productService, Product $product)
    {
        $productService->delete($product->id);

        return redirect()->route('admin.products.index');
    }

    public function activate(ProductService $productService, $id)
    {
        // إن لم تكن عندك activate/deactivate في ProductService بعد، قل لي لأعطيكها كملف/تعديل لاحق
        $productService->activate($id);
        return back()->with('success', 'Product activated successfully');
    }

    public function deactivate(ProductService $productService, $id)
    {
        $productService->deactivate($id);
        return back()->with('success', 'Product deactivated successfully');
    }
}
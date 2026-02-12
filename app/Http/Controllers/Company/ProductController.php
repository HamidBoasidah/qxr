<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

use App\Services\ProductService;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;

use App\DTOs\ProductDTO;
use App\Http\Requests\Company\StoreProductRequest;
use App\Http\Requests\Company\UpdateProductRequest;

class ProductController extends Controller
{
    public function __construct()
    {
        // Ensure company routes require an authenticated web user (no permissions system)
        $this->middleware('auth:web');
    }

    public function index(Request $request, ProductService $productService)
    {
        $perPage = (int) $request->input('per_page', 10);

        // Filter products by authenticated company user
        /** @var User|null $user */
        $user = Auth::guard('web')->user();
        if (!$user) {
            abort(403);
        }
        $products = $user->products()
            ->with(['category', 'tags', 'images'])
            ->paginate($perPage);

        // Transform products to DTOs
        $products->getCollection()->transform(function ($product) {
            return ProductDTO::fromModel($product)->toIndexArray();
        });

        return Inertia::render('Company/Product/Index', [
            'products' => $products
        ]);
    }

    public function create()
    {
        // authorize that the current user can create products (must be company)
        $this->authorize('create', Product::class);
        // ✅ أقسام المنتجات فقط
        $categories = Category::where('category_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name']);

        // ✅ وسوم المنتجات فقط
        $tags = Tag::where('tag_type', 'product')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Company/Product/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $productService)
    {
        // Authorize creation (company users only)
        $this->authorize('create', Product::class);

        // Get validated data
        $data = $request->validatedPayload();

        // Set company user ID from authenticated user
        $data['company_user_id'] = Auth::guard('web')->id();

        $productService->create(
            $data,
            $request->tagIds(),
            $request->imagesPayload()
        );

        return redirect()->route('company.products.index');
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

        return Inertia::render('Company/Product/Show', [
            'product' => $productDTO,
        ]);
    }

    public function edit(Product $product)
    {
        // Authorize: check if user owns this product
        $this->authorize('update', $product);

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

        return Inertia::render('Company/Product/Edit', [
            'product' => $productDTO,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function update(UpdateProductRequest $request, ProductService $productService, Product $product)
    {
        // Authorize: check if user owns this product
        $this->authorize('update', $product);

        $data = $request->validatedPayload();

        $productService->update(
            $product->id,
            $data,
            $request->tagIdsOrNull(),
            $request->imagesPayloadOrNull(),
            // delete ids if provided
            method_exists($request, 'deleteImageIdsOrNull') ? $request->deleteImageIdsOrNull() : null
        );

        return redirect()->route('company.products.index');
    }

    public function destroy(ProductService $productService, Product $product)
    {
        // Authorize: check if user owns this product
        $this->authorize('delete', $product);

        $productService->delete($product->id);

        return redirect()->route('company.products.index');
    }

    public function activate(ProductService $productService, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('activate', $product);

        $productService->activate($id);
        return back()->with('success', 'Product activated successfully');
    }

    public function deactivate(ProductService $productService, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('deactivate', $product);

        $productService->deactivate($id);
        return back()->with('success', 'Product deactivated successfully');
    }
}
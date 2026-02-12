<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ProductService;
use App\Models\Product;

use App\DTOs\ProductDTO;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:products.view')->only(['index', 'show']);
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
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\DTOs\OfferDTO;

use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;

use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;

class OfferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:offers.view')->only(['index', 'show']);
        $this->middleware('permission:offers.create')->only(['create', 'store']);
        $this->middleware('permission:offers.update')->only(['edit', 'update']);
        $this->middleware('permission:offers.delete')->only(['destroy']);
    }

    public function index(Request $request, OfferService $offerService)
    {
        $perPage = (int) $request->input('per_page', 10);

        // ✅ تحميل خفيف + counts من الريبوزيتوري
        $offers = $offerService->paginateForIndex($perPage, Auth::id());

        $offers->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toIndexArray();
        });

        return Inertia::render('Admin/Offer/Index', [
            'offers' => $offers,
        ]);
    }

    public function create()
    {
        $companyId = Auth::id();

        // قوائم اختيار للواجهة (ممكن لاحقًا ننقلها لسيرفس مستقل)
        $products = Product::where('company_user_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'base_price']);

        $customerCategories = Category::where('category_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $customerTags = Tag::where('tag_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $customers = User::where('user_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Admin/Offer/Create', [
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function store(StoreOfferRequest $request, OfferService $offerService)
    {
        $data = $request->validatedPayload();
        $data['company_user_id'] = Auth::id();

        $offerService->create(
            $data,
            $request->itemsPayload(),
            $request->targetsPayload()
        );

        return redirect()->route('admin.offers.index');
    }

    public function show(int $id, OfferService $offerService)
    {
        // ✅ تحميل كامل من الريبوزيتوري
        $offer = $offerService->findForShow($id);

        return Inertia::render('Admin/Offer/Show', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
        ]);
    }

    public function edit(int $id, OfferService $offerService)
    {
        $companyId = Auth::id();

        // ✅ تحميل كامل من الريبوزيتوري
        $offer = $offerService->findForShow($id);

        $products = Product::where('company_user_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'base_price']);

        $customerCategories = Category::where('category_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $customerTags = Tag::where('tag_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $customers = User::where('user_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Admin/Offer/Edit', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateOfferRequest $request, int $id, OfferService $offerService)
    {
        $data = $request->validatedPayload();

        $offerService->update(
            $id,
            $data,
            $request->itemsPayloadOrNull(),
            $request->targetsPayloadOrNull()
        );

        return redirect()->route('admin.offers.index');
    }

    public function destroy(int $id, OfferService $offerService)
    {
        $offerService->delete($id);

        return redirect()->route('admin.offers.index');
    }
}
<?php

namespace App\Http\Controllers\Company;

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
use App\Models\Offer;

use App\Http\Requests\Company\StoreOfferRequest;
use App\Http\Requests\Company\UpdateOfferRequest;

class OfferController extends Controller
{
    public function __construct()
    {
        // Remove permission middleware for company users
        // Companies don't use Spatie permissions
    }

    public function index(Request $request, OfferService $offerService)
    {
        $perPage = (int) $request->input('per_page', 10);

        // Filter offers by authenticated company user
        $user = Auth::guard('web')->user();
        $offers = $offerService->paginateForIndex($perPage, $user->id);

        $offers->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toIndexArray();
        });

        return Inertia::render('Company/Offer/Index', [
            'offers' => $offers,
        ]);
    }

    public function create()
    {
        $companyId = Auth::guard('web')->id();

        // قوائم اختيار للواجهة
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

        return Inertia::render('Company/Offer/Create', [
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function store(StoreOfferRequest $request, OfferService $offerService)
    {
        $data = $request->validatedPayload();
        $data['company_user_id'] = Auth::guard('web')->id();

        $offerService->create(
            $data,
            $request->itemsPayload(),
            $request->targetsPayload()
        );

        return redirect()->route('company.offers.index');
    }

    public function show(int $id, OfferService $offerService)
    {
        // ✅ تحميل كامل من الريبوزيتوري
        $offer = $offerService->findForShow($id);

        return Inertia::render('Company/Offer/Show', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
        ]);
    }

    public function edit(Offer $offer, OfferService $offerService)
    {
        // Authorize: check if user owns this offer
        $this->authorize('update', $offer);

        $companyId = Auth::guard('web')->id();

        // تحميل كامل من الريبوزيتوري
        $offer = $offerService->findForShow($offer->id);

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

        return Inertia::render('Company/Offer/Edit', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer, OfferService $offerService)
    {
        // Authorize: check if user owns this offer
        $this->authorize('update', $offer);

        $data = $request->validatedPayload();

        $offerService->update(
            $offer->id,
            $data,
            $request->itemsPayloadOrNull(),
            $request->targetsPayloadOrNull()
        );

        return redirect()->route('company.offers.index');
    }

    public function destroy(Offer $offer, OfferService $offerService)
    {
        // Authorize: check if user owns this offer
        $this->authorize('delete', $offer);

        $offerService->delete($offer->id);

        return redirect()->route('company.offers.index');
    }
}
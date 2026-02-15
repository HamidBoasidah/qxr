<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\DTOs\OfferDTO;
use App\Models\Offer;

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
        // Ensure company routes require an authenticated web user (no permissions system)
        $this->middleware('auth:web');
    }

    public function index(Request $request, OfferService $offerService)
    {
        $this->authorize('viewAny', Offer::class);

        $perPage = (int) $request->input('per_page', 10);

        $offers = $offerService->paginateForIndex($perPage, Auth::id());

        $offers->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toIndexArray();
        });

        return Inertia::render('Company/Offer/Index', [
            'offers' => $offers,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Offer::class);

        $companyId = Auth::id();

        $products = Product::where('company_user_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'base_price']);

        $customerCategories = Category::where('category_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $customerTags = Tag::where('tag_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $customers = $this->getFormattedCustomers();

        return Inertia::render('Company/Offer/Create', [
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function store(StoreOfferRequest $request, OfferService $offerService)
    {
        $this->authorize('create', Offer::class);

        $data = $request->validatedPayload();
        $data['company_user_id'] = Auth::id();

        $offerService->create(
            $data,
            $request->itemsPayload(),
            $request->targetsPayload()
        );

        return redirect()->route('company.offers.index');
    }

    public function show(int $id, OfferService $offerService)
    {
        // نحمّل العرض "show" من السيرفس (يشمل العلاقات)
        $offer = $offerService->findForShow($id);

        $this->authorize('view', $offer);

        return Inertia::render('Company/Offer/Show', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
        ]);
    }

    public function edit(int $id, OfferService $offerService)
    {
        $offer = $offerService->findForShow($id);

        $this->authorize('update', $offer);

        $companyId = Auth::id();

        $products = Product::where('company_user_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'base_price']);

        $customerCategories = Category::where('category_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name']);

        $customerTags = Tag::where('tag_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'name', 'slug']);

        $customers = $this->getFormattedCustomers();

        return Inertia::render('Company/Offer/Edit', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
            'products' => $products,
            'customerCategories' => $customerCategories,
            'customerTags' => $customerTags,
            'customers' => $customers,
        ]);
    }

    public function update(UpdateOfferRequest $request, int $id, OfferService $offerService)
    {
        // نجيب العرض plain للتحقق من الملكية قبل أي تحديث
        $offer = $offerService->findForShow($id); // (ممكن لاحقًا نعمل findPlainForAuth لتخفيفه)
        $this->authorize('update', $offer);

        $data = $request->validatedPayload();

        $offerService->update(
            $id,
            $data,
            $request->itemsPayloadOrNull(),
            $request->targetsPayloadOrNull()
        );

        return redirect()->route('company.offers.index');
    }

    public function destroy(int $id, OfferService $offerService)
    {
        $offer = $offerService->findForShow($id);
        $this->authorize('delete', $offer);

        $offerService->delete($id);

        return redirect()->route('company.offers.index');
    }

    /**
     * Helper method to format customers with full name
     */
    private function getFormattedCustomers()
    {
        return User::where('user_type', 'customer')
            ->where('is_active', true)
            ->get(['id', 'first_name', 'last_name'])
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name) ?: 'N/A',
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                ];
            });
    }
}
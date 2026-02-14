<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\DTOs\OfferDTO;

class OfferController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('permission:offers.view')->only(['index', 'show']);
    }

    public function index(Request $request, OfferService $offerService)
    {
        $perPage = (int) $request->input('per_page', 10);

        $offers = $offerService->paginateForIndex($perPage);

        $offers->getCollection()->transform(function ($offer) {
            return OfferDTO::fromModel($offer)->toIndexArray();
        });

        return Inertia::render('Admin/Offer/Index', [
            'offers' => $offers,
        ]);
    }


    public function show(int $id, OfferService $offerService)
    {
        // نحمّل العرض "show" من السيرفس (يشمل العلاقات)
        $offer = $offerService->findForShow($id);

        return Inertia::render('Admin/Offer/Show', [
            'offer' => OfferDTO::fromModel($offer)->toArray(),
        ]);
    }

}
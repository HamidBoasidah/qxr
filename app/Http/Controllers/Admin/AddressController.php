<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AddressService;
use App\DTOs\AddressDTO;
use App\Models\Address;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:addresses.view')->only(['index', 'show']);
    }

    public function index(Request $request, AddressService $addressService)
    {
        $perPage = $request->input('per_page', 9);
        $addresses = $addressService->paginate($perPage);
        $addresses->getCollection()->transform(function ($address) {
            return AddressDTO::fromModel($address)->toIndexArray();
        });
        return Inertia::render('Admin/Address/Index', [
            'addresses' => $addresses
        ]);
    }

    public function show(Address $address)
    {
        $dto = AddressDTO::fromModel($address)->toArray();
        return Inertia::render('Admin/Address/Show', [
            'address' => $dto,
        ]);
    }
}

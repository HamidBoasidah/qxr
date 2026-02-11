<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Services\AddressService;
use App\DTOs\AddressDTO;
use App\Models\Address;
use App\Models\Governorate;
use App\Models\District;
use App\Models\Area;
use Inertia\Inertia;

class AddressController extends Controller
{
    public function __construct()
    {
        // Ensure only authenticated (web) users can access company address routes
        $this->middleware('auth:web');

        // Use policy-based authorization for resource methods (view/show/edit/update/destroy)
        $this->authorizeResource(Address::class, 'address');
    }

    public function index(Request $request, AddressService $addressService)
    {
        $perPage = $request->input('per_page', 9);

        // Return only addresses belonging to the authenticated company user
        $userId = Auth::guard('web')->id();
        $addresses = $addressService->paginateForUser($userId, $perPage);
        $addresses->getCollection()->transform(function ($address) {
            return AddressDTO::fromModel($address)->toIndexArray();
        });
        return Inertia::render('Company/Address/Index', [
            'addresses' => $addresses
        ]);
    }

    public function create()
    {
        // include relation keys so frontend can filter by governorate/district when needed
        $governorates = Governorate::all(['id', 'name_ar', 'name_en']);
        $districts = District::all(['id', 'name_ar', 'name_en', 'governorate_id']);
        $areas = Area::all(['id', 'name_ar', 'name_en', 'district_id']);
        return Inertia::render('Company/Address/Create', [
            'governorates' => $governorates,
            'districts' => $districts,
            'areas' => $areas,
        ]);
    }

    public function store(StoreAddressRequest $request, AddressService $addressService)
    {
        $data = $request->validated();

        // Ensure the new address is always linked to the authenticated user
        $data['user_id'] = Auth::guard('web')->id();

        $addressService->create($data);
        return redirect()->route('company.addresses.index');
    }

    public function show(Address $address)
    {
        $dto = AddressDTO::fromModel($address)->toArray();
        return Inertia::render('Company/Address/Show', [
            'address' => $dto,
        ]);
    }

    public function edit(Address $address)
    {
        $governorates = Governorate::all(['id', 'name_ar', 'name_en']);
        // include relation keys so the frontend computed filters work correctly
        $districts = District::where('governorate_id', $address->governorate_id)->get(['id','name_ar','name_en','governorate_id']);
        $areas = Area::where('district_id', $address->district_id)->get(['id','name_ar','name_en','district_id']);
        $dto = AddressDTO::fromModel($address)->toArray();
        return Inertia::render('Company/Address/Edit', [
            'address' => $dto,
            'governorates' => $governorates,
            'districts' => $districts,
            'areas' => $areas,
        ]);
    }

    public function update(UpdateAddressRequest $request, AddressService $addressService, Address $address)
    {
        $data = $request->validated();

        // Prevent changing owner from the form
        unset($data['user_id']);

        $addressService->update($address->id, $data);
        return redirect()->route('company.addresses.index');
    }

    public function destroy(AddressService $addressService, Address $address)
    {
        $addressService->delete($address->id);
        return redirect()->route('company.addresses.index');
    }

    public function activate(AddressService $addressService, $id)
    {
        $address = $addressService->find($id);
        $this->authorize('activate', $address);

        $addressService->activate($id);
        return back()->with('success', 'Address activated successfully');
    }

    public function deactivate(AddressService $addressService, $id)
    {
        $address = $addressService->find($id);
        $this->authorize('deactivate', $address);

        $addressService->deactivate($id);
        return back()->with('success', 'Address deactivated successfully');
    }

    public function setDefault(AddressService $addressService, $id)
    {
        $address = $addressService->find($id);
        $this->authorize('setAsDefault', $address);

        $addressService->setDefaultForUser($id, $address->user_id);

        return back()->with('success', 'Address set as default successfully');
    }
}

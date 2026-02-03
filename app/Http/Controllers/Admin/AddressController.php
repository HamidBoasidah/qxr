<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $this->middleware('permission:addresses.view')->only(['index', 'show']);
        $this->middleware('permission:addresses.create')->only(['create', 'store']);
        $this->middleware('permission:addresses.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:addresses.delete')->only(['destroy']);
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

    public function create()
    {
        // include relation keys so frontend can filter by governorate/district when needed
        $governorates = Governorate::all(['id', 'name_ar', 'name_en']);
        $districts = District::all(['id', 'name_ar', 'name_en', 'governorate_id']);
        $areas = Area::all(['id', 'name_ar', 'name_en', 'district_id']);
        return Inertia::render('Admin/Address/Create', [
            'governorates' => $governorates,
            'districts' => $districts,
            'areas' => $areas,
        ]);
    }

    public function store(StoreAddressRequest $request, AddressService $addressService)
    {
        $data = $request->validated();

        $addressService->create($data);
        return redirect()->route('admin.addresses.index');
    }

    public function show(Address $address)
    {
        $dto = AddressDTO::fromModel($address)->toArray();
        return Inertia::render('Admin/Address/Show', [
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
        return Inertia::render('Admin/Address/Edit', [
            'address' => $dto,
            'governorates' => $governorates,
            'districts' => $districts,
            'areas' => $areas,
        ]);
    }

    public function update(UpdateAddressRequest $request, AddressService $addressService, Address $address)
    {
        $data = $request->validated();

        $addressService->update($address->id, $data);
        return redirect()->route('admin.addresses.index');
    }

    public function destroy(AddressService $addressService, Address $address)
    {
        $addressService->delete($address->id);
        return redirect()->route('admin.addresses.index');
    }

    public function activate(AddressService $addressService, $id)
    {
        $addressService->activate($id);
        return back()->with('success', 'Address activated successfully');
    }

    public function deactivate(AddressService $addressService, $id)
    {
        $addressService->deactivate($id);
        return back()->with('success', 'Address deactivated successfully');
    }

    public function setDefault(AddressService $addressService, $id)
    {
        // Admin can set any address as default for its owner
        $address = $addressService->find($id);

        $addressService->setDefaultForUser($id, $address->user_id);

        return back()->with('success', 'Address set as default successfully');
    }
}

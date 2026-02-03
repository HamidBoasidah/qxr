<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreDistrictRequest;
use App\Http\Requests\UpdateDistrictRequest;
use App\Services\DistrictService;
use App\DTOs\DistrictDTO;
use App\Models\District;
use App\Models\Governorate;
use Inertia\Inertia;

class DistrictController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:districts.view')->only(['index', 'show']);
        $this->middleware('permission:districts.create')->only(['create', 'store']);
        $this->middleware('permission:districts.update')->only(['edit', 'update' , 'activate', 'deactivate']);
        $this->middleware('permission:districts.delete')->only(['destroy']);
    }

    public function index(Request $request, DistrictService $districtService)
    {
        $perPage = $request->input('per_page', 10);
        $districts = $districtService->paginate($perPage);
        $districts->getCollection()->transform(function ($district) {
            return DistrictDTO::fromModel($district)->toIndexArray();
        });
        return Inertia::render('Admin/District/Index', [
            'districts' => $districts
        ]);
    }

    public function create()
    {
        // need governorates for selection
        $governorates = Governorate::all(['id', 'name_ar', 'name_en']);
        return Inertia::render('Admin/District/Create', [
            'governorates' => $governorates,
        ]);
    }

    public function store(StoreDistrictRequest $request, DistrictService $districtService)
    {
        $data = $request->validated();
        $districtService->create($data);
        return redirect()->route('admin.districts.index');
    }

    public function show(District $district)
    {
        $dto = DistrictDTO::fromModel($district)->toArray();
        return Inertia::render('Admin/District/Show', [
            'district' => $dto,
        ]);
    }

    public function edit(District $district)
    {
        $governorates = Governorate::all(['id', 'name_ar', 'name_en']);
        $dto = DistrictDTO::fromModel($district)->toArray();
        return Inertia::render('Admin/District/Edit', [
            'district' => $dto,
            'governorates' => $governorates,
        ]);
    }

    public function update(UpdateDistrictRequest $request, DistrictService $districtService, District $district)
    {
        $data = $request->validated();
        $districtService->update($district->id, $data);
        return redirect()->route('admin.districts.index');
    }

    public function destroy(DistrictService $districtService, District $district)
    {
        $districtService->delete($district->id);
        return redirect()->route('admin.districts.index');
    }

    public function activate(DistrictService $districtService, $id)
    {
        $districtService->activate($id);
        return back()->with('success', 'District activated successfully');
    }

    public function deactivate(DistrictService $districtService, $id)
    {
        $districtService->deactivate($id);
        return back()->with('success', 'District deactivated successfully');
    }
}

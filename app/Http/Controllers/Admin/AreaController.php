<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\AreaService;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;

use App\DTOs\AreaDTO;
use App\Models\Area;
use App\Models\District;

class AreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:areas.view')->only(['index', 'show']);
        $this->middleware('permission:areas.create')->only(['create', 'store']);
        $this->middleware('permission:areas.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:areas.delete')->only(['destroy']);
    }

    public function index(Request $request, AreaService $areaService)
    {
        $perPage = (int) $request->input('per_page', 10);
        // paginate already يحمل العلاقة district افتراضياً من AreaRepository::$defaultWith
        $areas = $areaService->paginate($perPage);

        $areas->getCollection()->transform(function ($area) {
            return AreaDTO::fromModel($area)->toIndexArray();
        });

        return Inertia::render('Admin/Area/Index', [
            'areas' => $areas,
        ]);
    }

    public function create()
    {
        // نحتاج قائمة المديريات للاختيار
        $districts = District::query()->select(['id', 'name_ar', 'name_en'])->get();

        return Inertia::render('Admin/Area/Create', [
            'districts' => $districts,
        ]);
    }

    public function store(StoreAreaRequest $request, AreaService $areaService)
    {
        $data = $request->validated();
        $areaService->create($data);

        return redirect()->route('admin.areas.index');
    }

    public function show(Area $area)
    {
        // إن كان DTO يعتمد على علاقات، AreaRepository يحمّل district افتراضياً،
        // أما هنا فلدقة أكبر يمكن تحميلها:
        $area->loadMissing(['district:id,name_ar,name_en']);

        $dto = AreaDTO::fromModel($area)->toArray();

        return Inertia::render('Admin/Area/Show', [
            'area' => $dto,
        ]);
    }

    public function edit(Area $area)
    {
        $area->loadMissing(['district:id,name_ar,name_en']);

        $districts = District::query()->select(['id', 'name_ar', 'name_en'])->get();
        $dto = AreaDTO::fromModel($area)->toArray();

        return Inertia::render('Admin/Area/Edit', [
            'area' => $dto,
            'districts' => $districts,
        ]);
    }

    public function update(UpdateAreaRequest $request, AreaService $areaService, Area $area)
    {
        $data = $request->validated();
        $areaService->update($area->id, $data);

        return redirect()->route('admin.areas.index');
    }

    public function destroy(AreaService $areaService, Area $area)
    {
        $areaService->delete($area->id);

        return redirect()->route('admin.areas.index');
    }

    public function activate(AreaService $areaService, $id)
    {
        $areaService->activate($id);

        return back()->with('success', 'Area activated successfully');
    }

    public function deactivate(AreaService $areaService, $id)
    {
        $areaService->deactivate($id);

        return back()->with('success', 'Area deactivated successfully');
    }
}
